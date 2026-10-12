<?php

use App\Enums\NewsletterTopic;

test('首頁顯示 Recca Chao 的簡介', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Recca Chao')
        ->assertSee('後端開發者、講師與社群推廣者');
});

test('首頁列出所有電子報並連到各自的訂閱頁面', function () {
    $response = $this->get(route('home'))->assertOk();

    foreach (NewsletterTopic::cases() as $topic) {
        $response->assertSee($topic->title())
            ->assertSee('href="'.route('newsletter.show', $topic).'"', false);
    }
});

test('首頁不再有電子報訂閱表單', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('action="'.route('newsletter.subscribe').'"', false)
        ->assertDontSee('name="email"', false);
});
