<?php

use App\Enums\NewsletterTopic;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\UniqueConstraintViolationException;

test('due 只包含預計寄送時間已到且尚未派送的期數', function () {
    $due = NewsletterIssue::factory()->scheduled(now()->subMinute())->create();
    NewsletterIssue::factory()->scheduled(now()->addHour())->create();
    NewsletterIssue::factory()->create();
    NewsletterIssue::factory()->scheduled(now()->subMinute())->dispatched()->create();

    expect(NewsletterIssue::due()->pluck('id')->all())->toBe([$due->id]);
});

test('recipients 只包含同主題已確認且未退訂的訂閱者', function () {
    $issue = NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament]);
    $ok = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Laravel)->confirmed()->create();
    NewsletterSubscriber::factory()->confirmed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->unsubscribed()->create();

    expect($issue->recipients()->pluck('id')->all())->toBe([$ok->id]);
});

test('status 依欄位推導', function () {
    expect(NewsletterIssue::factory()->create()->status())->toBe('未排程')
        ->and(NewsletterIssue::factory()->scheduled()->create()->status())->toBe('已排程')
        ->and(NewsletterIssue::factory()->scheduled()->dispatched()->create()->status())->toBe('派送中')
        ->and(NewsletterIssue::factory()->scheduled()->sent()->create()->status())->toBe('已寄出');
});

test('同一期同一位訂閱者只能有一筆寄送紀錄', function () {
    $issue = NewsletterIssue::factory()->create();
    $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

    $issue->deliveries()->create(['newsletter_subscriber_id' => $subscriber->id, 'sent_at' => now()]);

    expect(fn () => $issue->deliveries()->create(['newsletter_subscriber_id' => $subscriber->id, 'sent_at' => now()]))
        ->toThrow(UniqueConstraintViolationException::class);

    expect(NewsletterDelivery::count())->toBe(1);
});

test('同一主題的期號不能重複', function () {
    NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);

    expect(fn () => NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('resetToUnsent 清除寄出狀態並進入新一輪，同時保留舊的寄送紀錄', function () {
    $issue = NewsletterIssue::factory()->scheduled()->sent()->create();
    $subscriber = NewsletterSubscriber::factory()->confirmed()->create();
    $issue->deliveries()->create(['newsletter_subscriber_id' => $subscriber->id, 'sent_at' => now()]);

    $issue->resetToUnsent();
    $issue->refresh();

    expect($issue->status())->toBe('未排程')
        ->and($issue->scheduled_at)->toBeNull()
        ->and($issue->dispatched_at)->toBeNull()
        ->and($issue->sent_at)->toBeNull()
        ->and($issue->send_round)->toBe(2)
        ->and($issue->deliveries()->count())->toBe(1);
});

test('同一位訂閱者在不同輪次可以各有一筆寄送紀錄', function () {
    $issue = NewsletterIssue::factory()->create();
    $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

    $issue->deliveries()->create(['newsletter_subscriber_id' => $subscriber->id, 'send_round' => 1, 'sent_at' => now()]);
    $issue->deliveries()->create(['newsletter_subscriber_id' => $subscriber->id, 'send_round' => 2, 'sent_at' => now()]);

    expect($issue->deliveries()->count())->toBe(2);
});
