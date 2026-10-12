<?php

use App\Enums\NewsletterTopic;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\URL;

test('開啟退訂連結只顯示確認頁，不會直接退訂', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    $this->get($subscriber->unsubscribeUrl())
        ->assertOk()
        ->assertSee('確認取消訂閱');

    expect($subscriber->refresh()->unsubscribed_at)->toBeNull();
});

test('送出確認後才會標記退訂並顯示已取消訂閱', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    $this->post($subscriber->unsubscribeUrl())
        ->assertOk()
        ->assertSee('已取消訂閱');

    expect($subscriber->refresh()->unsubscribed_at)->not->toBeNull();
});

test('重複送出退訂不會覆寫第一次的退訂時間', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    $this->post($subscriber->unsubscribeUrl())->assertOk();
    $first = $subscriber->refresh()->unsubscribed_at;

    $this->travel(1)->hour();
    $this->post($subscriber->unsubscribeUrl())->assertOk();

    expect($subscriber->refresh()->unsubscribed_at->equalTo($first))->toBeTrue();
});

test('被竄改的退訂連結回 403 且不退訂', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    $tampered = route('newsletter.unsubscribe', ['email' => $subscriber->email, 'topic' => 'filament', 'signature' => 'bad']);

    $this->get($tampered)->assertForbidden();
    $this->post($tampered)->assertForbidden();

    expect($subscriber->refresh()->unsubscribed_at)->toBeNull();
});

test('簽章有效但找不到訂閱者時回 404', function () {
    $url = URL::signedRoute('newsletter.unsubscribe', ['email' => 'nobody@example.com', 'topic' => 'filament']);

    $this->get($url)->assertOk();
    $this->post($url)->assertNotFound();
});

test('退訂只影響該主題的訂閱', function () {
    $filament = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create(['email' => 'same@example.com']);
    $laravel = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Laravel)->confirmed()->create(['email' => 'same@example.com']);

    $this->post($filament->unsubscribeUrl())->assertOk();

    expect($filament->refresh()->unsubscribed_at)->not->toBeNull()
        ->and($laravel->refresh()->unsubscribed_at)->toBeNull();
});
