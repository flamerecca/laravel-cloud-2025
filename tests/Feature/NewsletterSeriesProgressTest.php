<?php

use App\Enums\NewsletterTopic;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;

function seriesSubscriber(array $attributes = []): NewsletterSubscriber
{
    return NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries()->create($attributes);
}

function publishedIssue(int $number, array $attributes = []): NewsletterIssue
{
    return NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament, 'number' => $number, ...$attributes]);
}

function deliver(NewsletterIssue $issue, NewsletterSubscriber $subscriber, int $round = 1): void
{
    NewsletterDelivery::create([
        'newsletter_issue_id' => $issue->id,
        'newsletter_subscriber_id' => $subscriber->id,
        'send_round' => $round,
        'sent_at' => now(),
    ]);
}

test('下一封是已發布且還沒收過的最小期號', function () {
    $subscriber = seriesSubscriber();
    $first = publishedIssue(1);
    $second = publishedIssue(2);

    expect($subscriber->nextIssue()->is($first))->toBeTrue();

    deliver($first, $subscriber);

    expect($subscriber->nextIssue()->is($second))->toBeTrue();
});

test('草稿、未來才發布與其他主題的期數都不算', function () {
    $subscriber = seriesSubscriber();
    NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);
    publishedIssue(2, ['scheduled_at' => now()->addDay()]);
    NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Ktor, 'number' => 3]);

    expect($subscriber->nextIssue())->toBeNull();
});

test('起始期號之前的期數會被略過', function () {
    $subscriber = seriesSubscriber(['series_start_number' => 3]);
    publishedIssue(1);
    publishedIssue(2);
    $third = publishedIssue(3);

    expect($subscriber->nextIssue()->is($third))->toBeTrue();
});

test('舊回合的寄送紀錄不算已收到這一期', function () {
    $subscriber = seriesSubscriber();
    $issue = publishedIssue(1, ['send_round' => 2]);
    deliver($issue, $subscriber, round: 1);

    expect($subscriber->nextIssue()->is($issue))->toBeTrue();

    deliver($issue, $subscriber, round: 2);

    expect($subscriber->nextIssue())->toBeNull();
});

test('廣播名單排除系列模式訂閱者', function () {
    $issue = publishedIssue(1);
    $broadcast = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    seriesSubscriber();

    expect($issue->recipients()->pluck('id')->all())->toBe([$broadcast->id]);
});

test('進度與下一封的標籤', function () {
    $subscriber = seriesSubscriber();
    $first = publishedIssue(1);
    $second = publishedIssue(2);
    $broadcast = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    expect($subscriber->seriesProgress())->toBe('已收到 0 / 2 期')
        ->and($subscriber->nextIssueLabel())->toBe('第 1 期')
        ->and($broadcast->seriesProgress())->toBe('廣播')
        ->and($broadcast->nextIssueLabel())->toBe('廣播');

    deliver($first, $subscriber);
    deliver($second, $subscriber);

    expect($subscriber->seriesProgress())->toBe('已收到 2 / 2 期')
        ->and($subscriber->nextIssueLabel())->toBe('已追上');
});

test('seriesDue 只包含已確認、未退訂且時間已到的系列模式訂閱者', function () {
    $due = seriesSubscriber();
    seriesSubscriber(['series_next_at' => now()->addHour()]);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries()->unsubscribed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->inSeries()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    expect(NewsletterSubscriber::seriesDue()->pluck('id')->all())->toBe([$due->id]);
});
