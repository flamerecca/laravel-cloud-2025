<?php

use App\Enums\NewsletterTopic;
use App\Models\User;
use Illuminate\Support\Facades\Vite;

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
            ->assertSee('href="'.route('newsletter.show', $topic->slug()).'"', false);
    }
});

test('首頁不再有電子報訂閱表單', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('action="'.route('newsletter.subscribe').'"', false)
        ->assertDontSee('name="email"', false);
});

test('首頁使用 reccaChao.png 作為 favicon', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('<link rel="icon" type="image/png" href="'.Vite::asset('resources/images/reccaChao.png').'">', false);
});

test('電子報頁面使用 reccaChao.png 作為 favicon', function () {
    $this->get(route('newsletter.index'))
        ->assertOk()
        ->assertSee('rel="icon"', false)
        ->assertSee(Vite::asset('resources/images/reccaChao.png'), false);
});

test('Filament 後台使用 reccaChao.png 作為 favicon', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee(Vite::asset('resources/images/reccaChao.png'), false);
});

test('登入後的首頁不顯示 Dashboard 按鈕', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Dashboard')
        ->assertDontSee('href="'.url('/dashboard').'"', false);
});

test('未登入的首頁仍顯示登入與註冊', function () {
    $this->get(route('home'))->assertSee('Log in')->assertSee('Register');
});
