<?php

use App\Jobs\StoreNewsletterSubscriber;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Support\InMemorySheetsTransport;

beforeEach(function () {
    InMemorySheetsTransport::reset();

    Schema::connection('google-sheets')->create('newsletter_subscribers', function (Blueprint $table) {
        $table->id();
        $table->string('email');
        $table->timestamp('subscribed_at')->nullable();
        $table->timestamps();
    });
});

test('有效 email 訂閱後顯示成功訊息並派送寫入任務', function () {
    Queue::fake();

    $response = $this->post(route('newsletter.subscribe'), [
        'email' => 'reader@example.com',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', '感謝訂閱！請到信箱查看確認信');

    Queue::assertPushed(StoreNewsletterSubscriber::class, fn (StoreNewsletterSubscriber $job) => $job->email === 'reader@example.com');
});

test('訂閱後會把訂閱者寫入 google-sheets 連線', function () {
    $this->post(route('newsletter.subscribe'), [
        'email' => 'reader@example.com',
    ]);

    expect(NewsletterSubscriber::where('email', 'reader@example.com')->count())->toBe(1);
});

test('重複訂閱同一個 email 不會建立第二筆資料', function () {
    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);
    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);

    expect(NewsletterSubscriber::where('email', 'reader@example.com')->count())->toBe(1);
});

test('email 格式錯誤時顯示錯誤訊息', function () {
    $response = $this->post(route('newsletter.subscribe'), [
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('email');
});

test('email 為必填', function () {
    $response = $this->post(route('newsletter.subscribe'), []);

    $response->assertSessionHasErrors('email');
});

test('短時間內送出超過限流門檻會回傳 429', function () {
    foreach (range(1, 6) as $i) {
        $this->post(route('newsletter.subscribe'), ['email' => "reader{$i}@example.com"]);
    }

    $response = $this->post(route('newsletter.subscribe'), ['email' => 'reader7@example.com']);

    $response->assertStatus(429);
});
