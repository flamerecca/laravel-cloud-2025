<?php

use App\Enums\NewsletterTopic;
use App\Jobs\StoreNewsletterSubscriber;
use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterTopicSetting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

function seriesReader(): NewsletterSubscriber
{
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();
    $subscriber->confirm();

    return $subscriber->refresh();
}

function issueNumber(int $number): NewsletterIssue
{
    return NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament, 'number' => $number]);
}

test('新確認的訂閱者馬上收到第 1 期，之後每隔一個間隔收到下一期', function () {
    issueNumber(1);
    issueNumber(2);
    issueNumber(3);
    $reader = seriesReader();

    $this->artisan('newsletter:send-series')->assertSuccessful();
    Mail::assertSent(NewsletterIssueMail::class, 1);
    Mail::assertSent(NewsletterIssueMail::class, fn ($mail) => $mail->hasTo($reader->email) && $mail->issue->number === 1);

    $this->travel(6)->days();
    $this->artisan('newsletter:send-series')->assertSuccessful();
    Mail::assertSent(NewsletterIssueMail::class, 1);

    $this->travel(1)->days();
    $this->artisan('newsletter:send-series')->assertSuccessful();
    Mail::assertSent(NewsletterIssueMail::class, 2);
    Mail::assertSent(NewsletterIssueMail::class, fn ($mail) => $mail->issue->number === 2);
});

test('連續執行兩次，同一位訂閱者只收到一封', function () {
    issueNumber(1);
    issueNumber(2);
    seriesReader();

    $this->artisan('newsletter:send-series')->assertSuccessful();
    $this->artisan('newsletter:send-series')->assertSuccessful();

    Mail::assertSent(NewsletterIssueMail::class, 1);
});

test('主題下沒有已發布的期數時不派送，也不推後下次可寄時間', function () {
    NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);
    $reader = seriesReader();
    $before = $reader->series_next_at;

    $this->artisan('newsletter:send-series')->assertSuccessful();

    Mail::assertNothingSent();
    expect($reader->refresh()->series_next_at->equalTo($before))->toBeTrue();
});

test('追上進度後不再寄，新期數發布後下一次執行就寄出', function () {
    issueNumber(1);
    seriesReader();
    $this->artisan('newsletter:send-series');
    Mail::assertSent(NewsletterIssueMail::class, 1);

    $this->travel(8)->days();
    $this->artisan('newsletter:send-series');
    Mail::assertSent(NewsletterIssueMail::class, 1);

    issueNumber(2);
    $this->artisan('newsletter:send-series');
    Mail::assertSent(NewsletterIssueMail::class, 2);
});

test('已被廣播流程標記為已寄出的期數，系列模式訂閱者仍然收得到', function () {
    NewsletterIssue::factory()->scheduled()->sent()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);
    seriesReader();

    $this->artisan('newsletter:send-series')->assertSuccessful();

    Mail::assertSent(NewsletterIssueMail::class, 1);
});

test('間隔依各電子報的設定', function () {
    NewsletterTopicSetting::factory()->create(['topic' => NewsletterTopic::Filament, 'interval_days' => 2]);
    issueNumber(1);
    issueNumber(2);
    seriesReader();

    $this->artisan('newsletter:send-series');
    $this->travel(2)->days();
    $this->artisan('newsletter:send-series');

    Mail::assertSent(NewsletterIssueMail::class, 2);
});

test('退訂、尚未確認與廣播模式的訂閱者不會被系列指令寄送', function () {
    issueNumber(1);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries()->unsubscribed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->inSeries()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    $this->artisan('newsletter:send-series')->assertSuccessful();

    Mail::assertNothingSent();
});

test('退訂後重新訂閱不會重寄已收過的期數', function () {
    issueNumber(1);
    issueNumber(2);
    $reader = seriesReader();
    $this->artisan('newsletter:send-series');
    Mail::assertSent(NewsletterIssueMail::class, 1);

    $reader->update(['unsubscribed_at' => now()]);
    StoreNewsletterSubscriber::dispatchSync($reader->email, now(), NewsletterTopic::Filament);
    $reader->refresh()->confirm();

    $this->artisan('newsletter:send-series');

    Mail::assertSent(NewsletterIssueMail::class, 2);
    Mail::assertSent(NewsletterIssueMail::class, fn ($mail) => $mail->issue->number === 2);
});

test('排程器每分鐘執行 newsletter:send-series', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'newsletter:send-series'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(10);
});

test('另一個程序在讀取後、搶佔前先搶走這位訂閱者時，這個程序不會再派送', function () {
    issueNumber(1);
    $reader = seriesReader();

    NewsletterSubscriber::retrieved(function (NewsletterSubscriber $subscriber) use ($reader): void {
        if ($subscriber->is($reader)) {
            // Another worker claims the subscriber behind this command's back.
            NewsletterSubscriber::query()->whereKey($reader->id)->update(['series_next_at' => now()->addDays(7)]);
        }
    });

    try {
        $this->artisan('newsletter:send-series')->assertSuccessful();
    } finally {
        NewsletterSubscriber::flushEventListeners();
    }

    Mail::assertNothingSent();
});
