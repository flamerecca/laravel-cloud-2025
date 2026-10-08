<?php

use App\Enums\NewsletterTopic;
use App\Jobs\StoreNewsletterSubscriber;
use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

test('有效 email 訂閱後顯示成功訊息並派送寫入任務', function () {
    Queue::fake();

    $response = $this->post(route('newsletter.subscribe'), [
        'email' => 'reader@example.com',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', '感謝訂閱！請到信箱點擊確認連結，完成訂閱');

    Queue::assertPushed(StoreNewsletterSubscriber::class, fn (StoreNewsletterSubscriber $job) => $job->email === 'reader@example.com');
});

test('訂閱後會把訂閱者寫入資料庫', function () {
    $this->post(route('newsletter.subscribe'), [
        'email' => 'reader@example.com',
    ]);

    expect(NewsletterSubscriber::forTopic(null)->where('email', 'reader@example.com')->count())->toBe(1);
});

test('重複訂閱同一個 email 不會建立第二筆資料', function () {
    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);
    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);

    expect(NewsletterSubscriber::forTopic(null)->where('email', 'reader@example.com')->count())->toBe(1);
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

test('Filament 進階分享訂閱頁面顯示主題與訂閱表單', function () {
    $response = $this->get(route('newsletter.show', NewsletterTopic::Filament));

    $response->assertOk();
    $response->assertSee('Filament 進階分享');
    $response->assertSee('action="'.route('newsletter.subscribe').'"', false);
    $response->assertSee('name="topic" value="filament"', false);
    $response->assertSee('name="email"', false);
});

test('訂閱卡片排在內容介紹之前', function () {
    $this->get(route('newsletter.show', NewsletterTopic::Filament))
        ->assertSeeInOrder(['訂閱電子報', '你會收到的內容']);
});

test('不存在的主題頁面回傳 404', function () {
    $this->get('/newsletter/unknown')->assertNotFound();
});

test('從主題頁面訂閱後導回該頁並顯示成功訊息', function () {
    Queue::fake();

    $page = route('newsletter.show', NewsletterTopic::Filament);

    $this->from($page)
        ->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'topic' => 'filament'])
        ->assertRedirect($page);

    $this->get($page)->assertSee('感謝訂閱！請到信箱點擊確認連結，完成訂閱');

    Queue::assertPushed(StoreNewsletterSubscriber::class, fn (StoreNewsletterSubscriber $job) => $job->topic === NewsletterTopic::Filament);
});

test('主題訂閱記錄為該主題，不算入一般電子報', function () {
    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'topic' => 'filament']);

    expect(NewsletterSubscriber::forTopic(NewsletterTopic::Filament)->where('email', 'reader@example.com')->count())->toBe(1)
        ->and(NewsletterSubscriber::forTopic(null)->where('email', 'reader@example.com')->count())->toBe(0);
});

test('同一個 email 可以同時訂閱一般電子報與主題電子報', function () {
    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);
    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'topic' => 'filament']);
    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'topic' => 'filament']);

    expect(NewsletterSubscriber::forTopic(null)->where('email', 'reader@example.com')->count())->toBe(1)
        ->and(NewsletterSubscriber::forTopic(NewsletterTopic::Filament)->where('email', 'reader@example.com')->count())->toBe(1);
});

test('不存在的主題無法訂閱', function () {
    Queue::fake();

    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'topic' => 'unknown'])
        ->assertSessionHasErrors(['topic' => '找不到這個電子報主題。']);

    Queue::assertNothingPushed();
});

test('主題頁面在確認失敗時顯示錯誤並保留輸入值', function () {
    $page = route('newsletter.show', NewsletterTopic::Filament);

    $this->from($page)
        ->post(route('newsletter.subscribe'), ['email' => 'not-an-email', 'topic' => 'filament'])
        ->assertRedirect($page);

    $this->get($page)
        ->assertSee('請輸入正確的 Email 格式。')
        ->assertSee('value="not-an-email"', false);
});

test('訂閱後寄出確認信，訂閱者在點擊連結前尚未確認', function () {
    Mail::fake();

    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'topic' => 'filament']);

    Mail::assertSent(NewsletterConfirmation::class, fn (NewsletterConfirmation $mail) => $mail->hasTo('reader@example.com')
        && $mail->topic === NewsletterTopic::Filament);

    expect(NewsletterSubscriber::forTopic(NewsletterTopic::Filament)->where('email', 'reader@example.com')->first()->isConfirmed())->toBeFalse();
});

test('確認信內容包含確認連結與主題名稱', function () {
    $mail = new NewsletterConfirmation('reader@example.com', NewsletterTopic::Filament);

    $mail->assertHasSubject('請確認訂閱「Filament 進階分享」')
        ->assertSeeInHtml('確認訂閱')
        ->assertSeeInHtml(e(route('newsletter.confirm')), false);
});

test('點擊確認連結後完成訂閱', function () {
    Mail::fake();

    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'topic' => 'filament']);

    $url = Mail::sent(NewsletterConfirmation::class)->first()->confirmUrl();

    $this->get($url)
        ->assertOk()
        ->assertSee('訂閱成功')
        ->assertSee('Filament 進階分享');

    expect(NewsletterSubscriber::forTopic(NewsletterTopic::Filament)->where('email', 'reader@example.com')->first()->isConfirmed())->toBeTrue();
});

test('一般電子報的確認連結只確認一般電子報的訂閱', function () {
    Mail::fake();

    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);
    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'topic' => 'filament']);

    $generalMail = Mail::sent(NewsletterConfirmation::class, fn (NewsletterConfirmation $mail) => $mail->topic === null)->first();

    $this->get($generalMail->confirmUrl())->assertOk();

    expect(NewsletterSubscriber::forTopic(null)->where('email', 'reader@example.com')->first()->isConfirmed())->toBeTrue()
        ->and(NewsletterSubscriber::forTopic(NewsletterTopic::Filament)->where('email', 'reader@example.com')->first()->isConfirmed())->toBeFalse();
});

test('已確認的訂閱者重複訂閱不會再收到確認信', function () {
    NewsletterSubscriber::forTopic(NewsletterTopic::Filament)->create([
        'email' => 'reader@example.com',
        'subscribed_at' => now(),
        'confirmed_at' => now(),
    ]);

    Mail::fake();

    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'topic' => 'filament']);

    Mail::assertNothingSent();
});

test('被竄改的確認連結無法完成訂閱', function () {
    Mail::fake();

    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);

    $url = Mail::sent(NewsletterConfirmation::class)->first()->confirmUrl();

    $this->get(str_replace('reader%40example.com', 'attacker%40example.com', $url))
        ->assertForbidden()
        ->assertSee('確認連結無效');

    expect(NewsletterSubscriber::forTopic(null)->where('email', 'reader@example.com')->first()->isConfirmed())->toBeFalse();
});

test('過期的確認連結無法完成訂閱', function () {
    Mail::fake();

    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);

    $url = Mail::sent(NewsletterConfirmation::class)->first()->confirmUrl();

    $this->travel(NewsletterConfirmation::LINK_EXPIRES_IN_DAYS + 1)->days();

    $this->get($url)->assertForbidden();

    expect(NewsletterSubscriber::forTopic(null)->where('email', 'reader@example.com')->first()->isConfirmed())->toBeFalse();
});

test('簽章有效但找不到訂閱者時顯示連結無效', function () {
    $url = URL::temporarySignedRoute('newsletter.confirm', now()->addDay(), ['email' => 'ghost@example.com']);

    $this->get($url)
        ->assertNotFound()
        ->assertSee('確認連結無效');
});

test('一般與主題訂閱存在同一張資料表，以 topic 欄位區分', function () {
    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com']);
    $this->post(route('newsletter.subscribe'), ['email' => 'reader@example.com', 'topic' => 'filament']);

    $this->assertDatabaseCount('newsletter_subscribers', 2);
    $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'reader@example.com', 'topic' => null]);
    $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'reader@example.com', 'topic' => 'filament']);

    expect(NewsletterSubscriber::forTopic(NewsletterTopic::Filament)->first()->topic)->toBe(NewsletterTopic::Filament);
});

test('電子報列表頁面列出所有主題並連到各自的訂閱頁面', function () {
    $response = $this->get(route('newsletter.index'));

    $response->assertOk()->assertSee('電子報列表');

    foreach (NewsletterTopic::cases() as $topic) {
        $response->assertSee($topic->title())
            ->assertSee('href="'.route('newsletter.show', $topic).'"', false);
    }
});

test('電子報列表頁面包含連到首頁的一般電子報', function () {
    $this->get('/newsletter')
        ->assertOk()
        ->assertSee(config('app.name').' 電子報')
        ->assertSee('href="'.route('home').'"', false);
});

test('POST /newsletter 仍然是訂閱表單的送出位置', function () {
    Queue::fake();

    $this->post('/newsletter', ['email' => 'reader@example.com'])
        ->assertRedirect()
        ->assertSessionHas('success');

    Queue::assertPushed(StoreNewsletterSubscriber::class);
});
