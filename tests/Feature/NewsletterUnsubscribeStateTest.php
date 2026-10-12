<?php

use App\Enums\NewsletterTopic;
use App\Jobs\StoreNewsletterSubscriber;
use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;

test('deliverable 只包含已確認且未退訂的訂閱者', function () {
    $ok = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->unsubscribed()->create();

    expect(NewsletterSubscriber::deliverable()->pluck('id')->all())->toBe([$ok->id]);
});

test('退訂後重新訂閱會清空確認與退訂時間並重寄確認信', function () {
    Mail::fake();

    $subscriber = NewsletterSubscriber::factory()
        ->forTopic(NewsletterTopic::Filament)
        ->confirmed()
        ->unsubscribed()
        ->create(['email' => 'back@example.com']);

    (new StoreNewsletterSubscriber('back@example.com', now(), NewsletterTopic::Filament))->handle();

    $subscriber->refresh();
    expect($subscriber->unsubscribed_at)->toBeNull()
        ->and($subscriber->confirmed_at)->toBeNull();

    Mail::assertSent(NewsletterConfirmation::class, 1);
});

test('unsubscribeUrl 是帶有簽章的退訂連結', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();

    $url = $subscriber->unsubscribeUrl();

    expect($url)->toContain('/newsletter/unsubscribe')
        ->toContain('signature=')
        ->toContain('topic=filament');
});

test('pending 只包含尚未確認且未退訂的訂閱者', function () {
    $pending = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->unsubscribed()->create();

    expect(NewsletterSubscriber::pending()->pluck('id')->all())->toBe([$pending->id]);
});

test('status 依確認與退訂時間推導', function () {
    expect(NewsletterSubscriber::factory()->create()->status())->toBe('尚未確認')
        ->and(NewsletterSubscriber::factory()->confirmed()->create()->status())->toBe('已確認')
        ->and(NewsletterSubscriber::factory()->confirmed()->unsubscribed()->create()->status())->toBe('已退訂');
});
