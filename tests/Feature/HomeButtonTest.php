<?php

use App\Enums\NewsletterTopic;
use App\Models\User;
use Illuminate\Support\Facades\URL;

test('公開頁面都有回到首頁的按鈕', function (string $url) {
    $this->get($url)
        ->assertSee('回到首頁')
        ->assertSee('href="'.route('home').'"', false);
})->with([
    'newsletter list' => fn () => route('newsletter.index'),
    'topic filament' => fn () => route('newsletter.show', NewsletterTopic::Filament->slug()),
    'topic laravel' => fn () => route('newsletter.show', NewsletterTopic::Laravel->slug()),
    'confirm invalid' => fn () => route('newsletter.confirm'),
    'unsubscribe confirm' => fn () => URL::signedRoute('newsletter.unsubscribe', ['email' => 'a@example.com']),
    'unsubscribe invalid' => fn () => route('newsletter.unsubscribe'),
    'shorten' => fn () => '/shorten',
]);

test('登入與註冊頁面有回到首頁的按鈕', function (string $url) {
    $this->get($url)->assertOk()->assertSee('回到首頁');
})->with(['/login', '/register', '/forgot-password']);

test('登入後的頁面有回到首頁的按鈕', function (string $url) {
    $this->actingAs(User::factory()->create());

    $this->get($url)->assertOk()->assertSee('回到首頁');
})->with(['/dashboard', '/settings/profile']);

test('Filament 後台有回到首頁的按鈕', function () {
    $this->actingAs(User::factory()->create(['email' => 'flamerecca711@gmail.com']));

    $this->get('/admin')->assertOk()->assertSee('回到首頁');
});

test('首頁本身不顯示回到首頁', function () {
    $this->get(route('home'))->assertOk()->assertDontSee('回到首頁');
});
