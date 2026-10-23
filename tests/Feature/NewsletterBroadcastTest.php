<?php

use App\Enums\NewsletterTopic;
use App\Jobs\SendNewsletterBroadcast;
use App\Mail\NewsletterBroadcastMail;
use App\Models\NewsletterBroadcast;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

function runBroadcast(NewsletterBroadcast $broadcast): ?int
{
    return $broadcast->dispatchToAll();
}

test('同一個 email 訂閱多個電子報只會收到一封', function () {
    $broadcast = NewsletterBroadcast::factory()->create();
    NewsletterSubscriber::factory()->confirmed()->create(['email' => 'a@example.com']);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create(['email' => 'a@example.com']);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Ktor)->confirmed()->inSeries()->create(['email' => 'A@Example.com']);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Laravel)->confirmed()->create(['email' => 'b@example.com']);

    expect(runBroadcast($broadcast))->toBe(2);

    Mail::assertSent(NewsletterBroadcastMail::class, 2);
    Mail::assertSent(NewsletterBroadcastMail::class, fn ($mail) => $mail->hasTo('a@example.com'));
    Mail::assertSent(NewsletterBroadcastMail::class, fn ($mail) => $mail->hasTo('b@example.com'));
});

test('未確認與已退訂的訂閱者不會收到', function () {
    $broadcast = NewsletterBroadcast::factory()->create();
    NewsletterSubscriber::factory()->create(['email' => 'pending@example.com']);
    NewsletterSubscriber::factory()->confirmed()->unsubscribed()->create(['email' => 'gone@example.com']);
    NewsletterSubscriber::factory()->confirmed()->create(['email' => 'ok@example.com']);

    runBroadcast($broadcast);

    Mail::assertSent(NewsletterBroadcastMail::class, 1);
    Mail::assertSent(NewsletterBroadcastMail::class, fn ($mail) => $mail->hasTo('ok@example.com'));
});

test('只退訂其中一個電子報的人仍然會收到一封', function () {
    $broadcast = NewsletterBroadcast::factory()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->unsubscribed()->create(['email' => 'a@example.com']);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Ktor)->confirmed()->create(['email' => 'a@example.com']);

    runBroadcast($broadcast);

    Mail::assertSent(NewsletterBroadcastMail::class, 1);
});

test('公告信沒有退訂連結也沒有退訂標頭', function () {
    $broadcast = NewsletterBroadcast::factory()->create(['title' => '重要公告', 'body' => '內文']);

    $mail = new NewsletterBroadcastMail($broadcast);

    $mail->assertSeeInHtml('重要公告');
    $mail->assertDontSeeInHtml('取消訂閱');
    expect(method_exists($mail, 'headers'))->toBeFalse();
});

test('重複派送同一則公告不會再寄', function () {
    $broadcast = NewsletterBroadcast::factory()->create();
    NewsletterSubscriber::factory()->confirmed()->create();

    expect($broadcast->dispatchToAll())->toBe(1)
        ->and($broadcast->fresh()->dispatchToAll())->toBeNull();

    Mail::assertSent(NewsletterBroadcastMail::class, 1);
});

test('同一個 email 的任務被重複執行也只會寄一次', function () {
    $broadcast = NewsletterBroadcast::factory()->create();
    NewsletterSubscriber::factory()->confirmed()->create(['email' => 'a@example.com']);

    dispatch_sync(new SendNewsletterBroadcast($broadcast->id, 'a@example.com'));
    dispatch_sync(new SendNewsletterBroadcast($broadcast->id, 'a@example.com'));

    Mail::assertSent(NewsletterBroadcastMail::class, 1);
    expect($broadcast->deliveries()->count())->toBe(1);
});

test('任務執行前已退訂的 email 不會收到', function () {
    $broadcast = NewsletterBroadcast::factory()->create();
    NewsletterSubscriber::factory()->confirmed()->unsubscribed()->create(['email' => 'a@example.com']);

    dispatch_sync(new SendNewsletterBroadcast($broadcast->id, 'a@example.com'));

    Mail::assertNothingSent();
});

test('寄信失敗時釋放紀錄讓重試可以再寄', function () {
    $broadcast = NewsletterBroadcast::factory()->create();
    NewsletterSubscriber::factory()->confirmed()->create(['email' => 'a@example.com']);

    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('smtp down'));

    expect(fn () => dispatch_sync(new SendNewsletterBroadcast($broadcast->id, 'a@example.com')))
        ->toThrow(RuntimeException::class);

    expect($broadcast->deliveries()->count())->toBe(0);
});

test('沒有可寄送的訂閱者時直接標記為已寄出', function () {
    $broadcast = NewsletterBroadcast::factory()->create();

    expect($broadcast->dispatchToAll())->toBe(0)
        ->and($broadcast->fresh()->status())->toBe('已寄出');
});

test('派送完成後標記為已寄出', function () {
    $broadcast = NewsletterBroadcast::factory()->create();
    NewsletterSubscriber::factory()->confirmed()->create();

    $broadcast->dispatchToAll();

    expect($broadcast->fresh()->status())->toBe('已寄出');
});

test('只寄給勾選的收件對象並且仍然只寄一封', function () {
    $broadcast = NewsletterBroadcast::factory()->forAudiences([NewsletterTopic::Filament->value, NewsletterBroadcast::GENERAL_AUDIENCE])->create();
    NewsletterSubscriber::factory()->confirmed()->create(['email' => 'general@example.com']);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create(['email' => 'filament@example.com']);
    NewsletterSubscriber::factory()->confirmed()->create(['email' => 'both@example.com']);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create(['email' => 'both@example.com']);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Ktor)->confirmed()->create(['email' => 'ktor@example.com']);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Ktor)->confirmed()->create(['email' => 'filament@example.com']);

    expect($broadcast->dispatchToAll())->toBe(3);

    Mail::assertSent(NewsletterBroadcastMail::class, 3);
    Mail::assertNotSent(NewsletterBroadcastMail::class, fn ($mail) => $mail->hasTo('ktor@example.com'));
});

test('只勾選一個主題時系列模式的訂閱者也會收到', function () {
    $broadcast = NewsletterBroadcast::factory()->forAudiences([NewsletterTopic::Ktor->value])->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Ktor)->confirmed()->inSeries()->create(['email' => 'series@example.com']);
    NewsletterSubscriber::factory()->confirmed()->create(['email' => 'general@example.com']);

    expect($broadcast->recipientEmails())->toBe(['series@example.com']);
});
