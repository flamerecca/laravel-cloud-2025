<?php

use App\Enums\NewsletterTopic;
use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

test('到期的期數寄給同主題已確認且未退訂的訂閱者並標記已寄出', function () {
    $issue = NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament]);
    $a = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    $b = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Laravel)->confirmed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->unsubscribed()->create();

    $this->artisan('newsletter:send-due')->assertSuccessful();

    Mail::assertSent(NewsletterIssueMail::class, 2);
    Mail::assertSent(NewsletterIssueMail::class, fn ($mail) => $mail->hasTo($a->email));
    Mail::assertSent(NewsletterIssueMail::class, fn ($mail) => $mail->hasTo($b->email));

    $issue->refresh();
    expect($issue->dispatched_at)->not->toBeNull()
        ->and($issue->sent_at)->not->toBeNull()
        ->and($issue->status())->toBe('已寄出');
});

test('連續執行兩次不會重複派送或重複寄信', function () {
    NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament]);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    $this->artisan('newsletter:send-due')->assertSuccessful();
    $this->artisan('newsletter:send-due')->assertSuccessful();

    Mail::assertSent(NewsletterIssueMail::class, 1);
});

test('尚未到時間或未排程的期數不派送', function () {
    NewsletterIssue::factory()->scheduled(now()->addHour())->create(['topic' => NewsletterTopic::Filament]);
    NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament, 'number' => 2]);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    $this->artisan('newsletter:send-due')->assertSuccessful();

    Mail::assertNothingSent();
});

test('沒有任何可寄送訂閱者的期數直接標記為已寄出', function () {
    $issue = NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament]);

    $this->artisan('newsletter:send-due')->assertSuccessful();

    $issue->refresh();
    expect($issue->dispatched_at)->not->toBeNull()
        ->and($issue->sent_at)->not->toBeNull();
    Mail::assertNothingSent();
});

test('排程器每分鐘執行 newsletter:send-due', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'newsletter:send-due'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(10);
});

test('派送途中發生例外時會釋放期數，下一輪可以重新派送', function () {
    $issue = NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament]);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    Bus::shouldReceive('batch')->once()->andThrow(new RuntimeException('job_batches insert failed'));

    expect(fn () => Artisan::call('newsletter:send-due'))->toThrow(RuntimeException::class);

    expect($issue->refresh()->dispatched_at)->toBeNull();
});
