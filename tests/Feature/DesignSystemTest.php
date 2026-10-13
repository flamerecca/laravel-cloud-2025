<?php

use App\Enums\NewsletterTopic;
use App\Mail\NewsletterConfirmation;
use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\URL;

test('公開頁面一律使用深色模式', function (string $url) {
    $this->get($url)
        ->assertOk()
        ->assertSee('class="dark"', false);
})->with([
    'home' => fn () => route('home'),
    'newsletter list' => fn () => route('newsletter.index'),
    'topic filament' => fn () => route('newsletter.show', NewsletterTopic::Filament->slug()),
    'topic laravel' => fn () => route('newsletter.show', NewsletterTopic::Laravel->slug()),
]);

test('確認與退訂頁面使用深色模式', function () {
    $this->get(route('newsletter.confirm'))->assertSee('class="dark"', false);
    $this->get(URL::signedRoute('newsletter.unsubscribe', ['email' => 'a@example.com']))
        ->assertSee('class="dark"', false);
});

test('頁面標題使用終端機風格的前綴樣式', function () {
    $this->get(route('home'))->assertSee('term-title', false);
    $this->get(route('newsletter.index'))->assertSee('term-title', false);
    $this->get(route('newsletter.show', NewsletterTopic::Laravel->slug()))->assertSee('term-title', false);
});

test('確認信使用參考站的深色背景與綠色標題', function () {
    $html = (new NewsletterConfirmation('reader@example.com', NewsletterTopic::Filament))->render();

    expect($html)->toContain('#151515')
        ->toContain('#b5e853');
});

test('電子報信件的連結使用參考站的藍色', function () {
    $issue = NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament]);
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    $html = (new NewsletterIssueMail($issue, $subscriber))->render();

    expect($html)->toContain('#151515')->toContain('#63c0f5');
});
