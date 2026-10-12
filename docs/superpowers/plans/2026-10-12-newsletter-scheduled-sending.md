# 定期寄送電子報 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 讓管理者在 Filament 後台維護每一期電子報與預計寄送時間，系統依排程透過 Mailgun 把到期的那一期寄給該主題下已確認且未退訂的訂閱者。

**Architecture:** 新增 `newsletter_issues` 與 `newsletter_deliveries` 兩張資料表。排程指令每分鐘搶佔到期期數，並為每位可寄送訂閱者派送一個 `SendNewsletterIssue` 工作，工作以 Bus Batch 執行。訂閱者資料表新增 `unsubscribed_at`，信件底部帶簽章退訂連結。

**Tech Stack:** Laravel 12，Filament 4，Pest，Livewire，Mailgun 透過 `symfony/mailgun-mailer` 與 `symfony/http-client`，這兩個套件已在 `composer.json`，不需要安裝。

**Spec:** `docs/superpowers/specs/2026-10-12-newsletter-scheduled-sending-design.md`

## Global Constraints

- 不修改 `composer.json`，不重新安裝任何套件。
- 只寄給 `confirmed_at` 不為空且 `unsubscribed_at` 為空的訂閱者，且 `topic` 必須與期數相同。首頁的一般電子報訂閱者，也就是 `topic` 為 null 的資料，不寄送。
- 同一組 `newsletter_issue_id` 加 `newsletter_subscriber_id` 只能有一筆 `newsletter_deliveries`。
- 測試一律使用 `Mail::fake()`，不呼叫 Mailgun。
- 使用者可見的文字使用台灣正體中文，中英文之間留半形空格，句中不使用括號補充說明。
- 每個任務完成前執行 `vendor/bin/pint --dirty`，並執行該任務涉及的測試。
- 每個任務最後的 commit 步驟，只在使用者要求提交時才執行，否則略過。

## Review Focus

- 排程指令連續執行兩次：第二次不能再派送同一期，也不能讓任何人收到第二封。測試在 Task 6。
- 工作重試：已有 `newsletter_deliveries` 的組合不能再寄。測試在 Task 5。
- 派送後、寄出前才退訂的訂閱者：不能收到信。測試在 Task 5。
- 沒有任何可寄送訂閱者的期數：不能卡在「派送中」，應直接標記為已寄出。測試在 Task 6。
- 期數內文含 `<script>` 或 `javascript:` 連結：寄出的信件不能包含。測試在 Task 4。
- 匯入指令重複執行：不產生重複資料，也不能覆寫已派送期數。測試在 Task 7。

---

## File Structure

新增：
- `database/migrations/*_add_unsubscribed_at_to_newsletter_subscribers_table.php`
- `database/migrations/*_create_newsletter_issues_table.php`
- `database/migrations/*_create_newsletter_deliveries_table.php`
- `app/Models/NewsletterIssue.php`、`app/Models/NewsletterDelivery.php`
- `database/factories/NewsletterIssueFactory.php`
- `app/Mail/NewsletterIssueMail.php`、`resources/views/mail/newsletter/issue.blade.php`
- `app/Jobs/SendNewsletterIssue.php`
- `app/Console/Commands/SendDueNewsletterIssues.php`、`app/Console/Commands/ImportNewsletterIssues.php`
- `app/Http/Controllers/NewsletterUnsubscribeController.php`、`resources/views/newsletter/unsubscribed.blade.php`
- `app/Filament/Resources/NewsletterIssues/` 下的 Resource、Schemas、Tables、Pages
- 對應測試檔，列於各任務

修改：
- `app/Models/NewsletterSubscriber.php`、`database/factories/NewsletterSubscriberFactory.php`
- `app/Jobs/StoreNewsletterSubscriber.php`
- `routes/web/newsletter.php`、`routes/console.php`
- `.env.example`

---

### Task 1: 訂閱者退訂欄位與重新訂閱流程

**Files:**
- Create: `database/migrations/<timestamp>_add_unsubscribed_at_to_newsletter_subscribers_table.php`
- Modify: `app/Models/NewsletterSubscriber.php`
- Modify: `database/factories/NewsletterSubscriberFactory.php`
- Modify: `app/Jobs/StoreNewsletterSubscriber.php`
- Test: `tests/Feature/NewsletterUnsubscribeStateTest.php`

**Interfaces:**
- Produces:
  - `NewsletterSubscriber::scopeDeliverable(Builder $query): void`，即 `NewsletterSubscriber::deliverable()`
  - `NewsletterSubscriber::unsubscribeUrl(): string`
  - 屬性 `unsubscribed_at`，datetime 或 null
  - Factory 狀態 `NewsletterSubscriber::factory()->unsubscribed()`

- [ ] **Step 1: 寫失敗測試**

建立 `tests/Feature/NewsletterUnsubscribeStateTest.php`：

```php
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
```

- [ ] **Step 2: 執行測試確認失敗**

Run: `php artisan test --filter=NewsletterUnsubscribeStateTest`
Expected: FAIL，找不到 `unsubscribed` 狀態與 `unsubscribed_at` 欄位。

- [ ] **Step 3: 建立遷移**

Run: `php artisan make:migration add_unsubscribed_at_to_newsletter_subscribers_table --table=newsletter_subscribers --no-interaction`

將 `up` 與 `down` 改成：

```php
public function up(): void
{
    Schema::table('newsletter_subscribers', function (Blueprint $table) {
        $table->timestamp('unsubscribed_at')->nullable()->after('confirmed_at');
    });
}

public function down(): void
{
    Schema::table('newsletter_subscribers', function (Blueprint $table) {
        $table->dropColumn('unsubscribed_at');
    });
}
```

- [ ] **Step 4: 修改模型**

`app/Models/NewsletterSubscriber.php`：

`$fillable` 加入 `'unsubscribed_at'`，`casts()` 加入 `'unsubscribed_at' => 'datetime'`，並在 `forTopic` 之前加入：

```php
/**
 * Subscribers who confirmed their address and have not unsubscribed since.
 *
 * @param  Builder<static>  $query
 */
public function scopeDeliverable(Builder $query): void
{
    $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
}

/**
 * The signed link in every newsletter that stops further deliveries.
 */
public function unsubscribeUrl(): string
{
    return URL::signedRoute(
        'newsletter.unsubscribe',
        array_filter(['email' => $this->email, 'topic' => $this->topic?->value]),
    );
}
```

並在檔案頂端加入 `use Illuminate\Support\Facades\URL;`。

- [ ] **Step 5: 修改 Factory**

`database/factories/NewsletterSubscriberFactory.php` 在 `confirmed()` 之後加入：

```php
/**
 * The subscriber used the unsubscribe link.
 */
public function unsubscribed(): static
{
    return $this->state(fn (array $attributes) => [
        'unsubscribed_at' => now(),
    ]);
}
```

- [ ] **Step 6: 修改重新訂閱流程**

`app/Jobs/StoreNewsletterSubscriber.php` 的 `handle()` 改成：

```php
public function handle(): void
{
    $subscriber = NewsletterSubscriber::forTopic($this->topic)->firstOrCreate(
        ['email' => $this->email],
        ['subscribed_at' => $this->subscribedAt],
    );

    // Coming back after unsubscribing starts over: the address has to be confirmed again.
    if ($subscriber->unsubscribed_at !== null) {
        $subscriber->update([
            'subscribed_at' => $this->subscribedAt,
            'confirmed_at' => null,
            'unsubscribed_at' => null,
        ]);
    }

    if ($subscriber->isConfirmed()) {
        return;
    }

    Mail::to($this->email)->send(new NewsletterConfirmation($this->email, $this->topic));
}
```

- [ ] **Step 7: 執行測試確認通過**

Run: `php artisan test --filter="NewsletterUnsubscribeStateTest|NewsletterSubscriptionTest"`
Expected: PASS。`unsubscribeUrl` 測試依賴 Task 3 的路由，此時若因找不到路由名稱而失敗，先把該測試標為 Task 3 完成後再驗證，其餘測試必須通過。

- [ ] **Step 8: Commit**

```bash
git add database/migrations app/Models/NewsletterSubscriber.php database/factories/NewsletterSubscriberFactory.php app/Jobs/StoreNewsletterSubscriber.php tests/Feature/NewsletterUnsubscribeStateTest.php
git commit -m "Track unsubscribes on newsletter subscribers"
```

---

### Task 2: 期數與寄送紀錄資料模型

**Files:**
- Create: `database/migrations/<timestamp>_create_newsletter_issues_table.php`
- Create: `database/migrations/<timestamp>_create_newsletter_deliveries_table.php`
- Create: `app/Models/NewsletterIssue.php`、`app/Models/NewsletterDelivery.php`
- Create: `database/factories/NewsletterIssueFactory.php`
- Test: `tests/Feature/NewsletterIssueModelTest.php`

**Interfaces:**
- Consumes: `NewsletterSubscriber::deliverable()`、`NewsletterSubscriber::forTopic()`
- Produces:
  - `NewsletterIssue::due(): Builder` scope，即 `NewsletterIssue::due()`
  - `NewsletterIssue::recipients(): Builder<NewsletterSubscriber>`
  - `NewsletterIssue::deliveries(): HasMany`
  - `NewsletterIssue::isDispatched(): bool`
  - `NewsletterIssue::status(): string`，回傳「未排程」「已排程」「派送中」「已寄出」其中之一
  - Factory 狀態：`scheduled(?Carbon $at = null)`、`dispatched()`、`sent()`

- [ ] **Step 1: 寫失敗測試**

```php
<?php

use App\Enums\NewsletterTopic;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;

test('due 只包含預計寄送時間已到且尚未派送的期數', function () {
    $due = NewsletterIssue::factory()->scheduled(now()->subMinute())->create();
    NewsletterIssue::factory()->scheduled(now()->addHour())->create();
    NewsletterIssue::factory()->create();
    NewsletterIssue::factory()->scheduled(now()->subMinute())->dispatched()->create();

    expect(NewsletterIssue::due()->pluck('id')->all())->toBe([$due->id]);
});

test('recipients 只包含同主題已確認且未退訂的訂閱者', function () {
    $issue = NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament]);
    $ok = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Laravel)->confirmed()->create();
    NewsletterSubscriber::factory()->confirmed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->unsubscribed()->create();

    expect($issue->recipients()->pluck('id')->all())->toBe([$ok->id]);
});

test('status 依欄位推導', function () {
    expect(NewsletterIssue::factory()->create()->status())->toBe('未排程')
        ->and(NewsletterIssue::factory()->scheduled()->create()->status())->toBe('已排程')
        ->and(NewsletterIssue::factory()->scheduled()->dispatched()->create()->status())->toBe('派送中')
        ->and(NewsletterIssue::factory()->scheduled()->sent()->create()->status())->toBe('已寄出');
});

test('同一期同一位訂閱者只能有一筆寄送紀錄', function () {
    $issue = NewsletterIssue::factory()->create();
    $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

    $issue->deliveries()->create(['newsletter_subscriber_id' => $subscriber->id, 'sent_at' => now()]);

    expect(fn () => $issue->deliveries()->create(['newsletter_subscriber_id' => $subscriber->id, 'sent_at' => now()]))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);

    expect(NewsletterDelivery::count())->toBe(1);
});

test('同一主題的期號不能重複', function () {
    NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);

    expect(fn () => NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
```

- [ ] **Step 2: 執行測試確認失敗**

Run: `php artisan test --filter=NewsletterIssueModelTest`
Expected: FAIL，找不到 `NewsletterIssue`。

- [ ] **Step 3: 建立遷移**

Run:
```bash
php artisan make:migration create_newsletter_issues_table --no-interaction
php artisan make:migration create_newsletter_deliveries_table --no-interaction
```

`create_newsletter_issues_table` 的 `up()`：

```php
Schema::create('newsletter_issues', function (Blueprint $table) {
    $table->id();
    // A NewsletterTopic value.
    $table->string('topic');
    $table->unsignedInteger('number');
    $table->string('title');
    $table->text('summary')->nullable();
    $table->longText('body');
    $table->timestamp('scheduled_at')->nullable();
    // Set the moment delivery jobs are queued, so an issue is never queued twice.
    $table->timestamp('dispatched_at')->nullable();
    $table->timestamp('sent_at')->nullable();
    $table->timestamps();

    $table->unique(['topic', 'number']);
    $table->index(['scheduled_at', 'dispatched_at']);
});
```

`down()`：`Schema::dropIfExists('newsletter_issues');`

`create_newsletter_deliveries_table` 的 `up()`：

```php
Schema::create('newsletter_deliveries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('newsletter_issue_id')->constrained()->cascadeOnDelete();
    $table->foreignId('newsletter_subscriber_id')->constrained()->cascadeOnDelete();
    $table->timestamp('sent_at');

    $table->unique(['newsletter_issue_id', 'newsletter_subscriber_id']);
});
```

`down()`：`Schema::dropIfExists('newsletter_deliveries');`

兩個檔案的時間戳記要讓 issues 排在 deliveries 之前，必要時調整檔名。

- [ ] **Step 4: 建立模型**

`app/Models/NewsletterIssue.php`：

```php
<?php

namespace App\Models;

use App\Enums\NewsletterTopic;
use Database\Factories\NewsletterIssueFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** @use HasFactory<NewsletterIssueFactory> */
class NewsletterIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic',
        'number',
        'title',
        'summary',
        'body',
        'scheduled_at',
        'dispatched_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'topic' => NewsletterTopic::class,
            'number' => 'integer',
            'scheduled_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<NewsletterDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NewsletterDelivery::class);
    }

    /**
     * Issues whose scheduled time has passed and that have not been queued yet.
     *
     * @param  Builder<static>  $query
     */
    public function scopeDue(Builder $query): void
    {
        $query->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->whereNull('dispatched_at');
    }

    /**
     * The subscribers this issue goes to.
     *
     * @return Builder<NewsletterSubscriber>
     */
    public function recipients(): Builder
    {
        return NewsletterSubscriber::forTopic($this->topic)->deliverable();
    }

    public function isDispatched(): bool
    {
        return $this->dispatched_at !== null;
    }

    public function status(): string
    {
        return match (true) {
            $this->sent_at !== null => '已寄出',
            $this->dispatched_at !== null => '派送中',
            $this->scheduled_at !== null => '已排程',
            default => '未排程',
        };
    }
}
```

`app/Models/NewsletterDelivery.php`：

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'newsletter_issue_id',
        'newsletter_subscriber_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<NewsletterIssue, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(NewsletterIssue::class, 'newsletter_issue_id');
    }

    /**
     * @return BelongsTo<NewsletterSubscriber, $this>
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(NewsletterSubscriber::class, 'newsletter_subscriber_id');
    }
}
```

- [ ] **Step 5: 建立 Factory**

Run: `php artisan make:factory NewsletterIssueFactory --model=NewsletterIssue --no-interaction`

`database/factories/NewsletterIssueFactory.php`：

```php
<?php

namespace Database\Factories;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterIssue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<NewsletterIssue>
 */
class NewsletterIssueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic' => NewsletterTopic::Filament,
            'number' => $this->faker->unique()->numberBetween(1, 1000),
            'title' => $this->faker->sentence(4),
            'summary' => $this->faker->sentence(),
            'body' => "## {$this->faker->sentence(3)}\n\n{$this->faker->paragraph()}",
        ];
    }

    public function scheduled(?Carbon $at = null): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => $at ?? now()->subMinute(),
        ]);
    }

    public function dispatched(): static
    {
        return $this->state(fn (array $attributes) => [
            'dispatched_at' => now(),
        ]);
    }

    public function sent(): static
    {
        return $this->dispatched()->state(fn (array $attributes) => [
            'sent_at' => now(),
        ]);
    }
}
```

- [ ] **Step 6: 執行測試確認通過**

Run: `php artisan test --filter=NewsletterIssueModelTest`
Expected: PASS，5 個測試。

- [ ] **Step 7: Commit**

```bash
git add database app/Models tests/Feature/NewsletterIssueModelTest.php
git commit -m "Add newsletter issues and deliveries"
```

---

### Task 3: 退訂路由與頁面

**Files:**
- Create: `app/Http/Controllers/NewsletterUnsubscribeController.php`
- Create: `resources/views/newsletter/unsubscribed.blade.php`
- Modify: `routes/web/newsletter.php`
- Test: `tests/Feature/NewsletterUnsubscribeTest.php`

**Interfaces:**
- Consumes: `NewsletterSubscriber::unsubscribeUrl()`、`NewsletterSubscriber::forTopic()`
- Produces: 具名路由 `newsletter.unsubscribe`，`GET /newsletter/unsubscribe`

- [ ] **Step 1: 寫失敗測試**

```php
<?php

use App\Enums\NewsletterTopic;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\URL;

test('有效的退訂連結會標記退訂並顯示確認頁', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    $this->get($subscriber->unsubscribeUrl())
        ->assertOk()
        ->assertSee('已取消訂閱');

    expect($subscriber->refresh()->unsubscribed_at)->not->toBeNull();
});

test('重複點擊退訂連結不會覆寫第一次的退訂時間', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    $this->get($subscriber->unsubscribeUrl())->assertOk();
    $first = $subscriber->refresh()->unsubscribed_at;

    $this->travel(1)->hour();
    $this->get($subscriber->unsubscribeUrl())->assertOk();

    expect($subscriber->refresh()->unsubscribed_at->equalTo($first))->toBeTrue();
});

test('被竄改的退訂連結回 403 且不退訂', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    $this->get(route('newsletter.unsubscribe', ['email' => $subscriber->email, 'topic' => 'filament', 'signature' => 'bad']))
        ->assertForbidden();

    expect($subscriber->refresh()->unsubscribed_at)->toBeNull();
});

test('簽章有效但找不到訂閱者時回 404', function () {
    $url = URL::signedRoute('newsletter.unsubscribe', ['email' => 'nobody@example.com', 'topic' => 'filament']);

    $this->get($url)->assertNotFound();
});

test('退訂只影響該主題的訂閱', function () {
    $filament = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create(['email' => 'same@example.com']);
    $laravel = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Laravel)->confirmed()->create(['email' => 'same@example.com']);

    $this->get($filament->unsubscribeUrl())->assertOk();

    expect($filament->refresh()->unsubscribed_at)->not->toBeNull()
        ->and($laravel->refresh()->unsubscribed_at)->toBeNull();
});
```

- [ ] **Step 2: 執行測試確認失敗**

Run: `php artisan test --filter=NewsletterUnsubscribeTest`
Expected: FAIL，找不到路由 `newsletter.unsubscribe`。

- [ ] **Step 3: 建立控制器**

`app/Http/Controllers/NewsletterUnsubscribeController.php`：

```php
<?php

namespace App\Http\Controllers;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NewsletterUnsubscribeController extends Controller
{
    /**
     * Stop deliveries from the signed link at the bottom of every newsletter.
     */
    public function __invoke(Request $request): Response
    {
        $topic = $request->enum('topic', NewsletterTopic::class);

        if (! $request->hasValidSignature()) {
            return response()->view('newsletter.unsubscribed', ['unsubscribed' => false, 'topic' => $topic], 403);
        }

        $subscriber = NewsletterSubscriber::forTopic($topic)
            ->where('email', $request->query('email'))
            ->first();

        if (! $subscriber) {
            return response()->view('newsletter.unsubscribed', ['unsubscribed' => false, 'topic' => $topic], 404);
        }

        if ($subscriber->unsubscribed_at === null) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return response()->view('newsletter.unsubscribed', ['unsubscribed' => true, 'topic' => $topic]);
    }
}
```

- [ ] **Step 4: 建立視圖**

`resources/views/newsletter/unsubscribed.blade.php`：

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $unsubscribed ? '已取消訂閱' : '退訂連結無效'])
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <main class="mx-auto flex min-h-svh w-full max-w-2xl flex-col justify-center gap-6 px-6 py-12">
            @if ($unsubscribed)
                <flux:callout variant="success" icon="check-circle" heading="已取消訂閱">
                    <flux:callout.text>
                        之後不會再收到「{{ $topic?->title() ?? config('app.name').' 電子報' }}」。
                    </flux:callout.text>
                </flux:callout>
            @else
                <flux:callout variant="danger" icon="x-circle" heading="退訂連結無效">
                    <flux:callout.text>
                        這個連結不完整或找不到對應的訂閱，請從最新一封電子報底部的連結再試一次。
                    </flux:callout.text>
                </flux:callout>
            @endif

            <flux:button :href="url('/')" class="self-start">回到首頁</flux:button>
        </main>
        @fluxScripts
    </body>
</html>
```

- [ ] **Step 5: 加入路由**

`routes/web/newsletter.php` 在 `newsletter.confirm` 路由之後、`newsletter.show` 之前加入，並在檔案頂端加入 `use App\Http\Controllers\NewsletterUnsubscribeController;`：

```php
Route::get('/newsletter/unsubscribe', NewsletterUnsubscribeController::class)
    ->middleware('throttle:6,1')
    ->name('newsletter.unsubscribe');
```

- [ ] **Step 6: 執行測試確認通過**

Run: `php artisan test --filter="NewsletterUnsubscribeTest|NewsletterUnsubscribeStateTest"`
Expected: PASS，包含 Task 1 的 `unsubscribeUrl` 測試。

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/NewsletterUnsubscribeController.php resources/views/newsletter/unsubscribed.blade.php routes/web/newsletter.php tests/Feature/NewsletterUnsubscribeTest.php
git commit -m "Add signed unsubscribe link for newsletters"
```

---

### Task 4: 電子報信件

**Files:**
- Create: `app/Mail/NewsletterIssueMail.php`
- Create: `resources/views/mail/newsletter/issue.blade.php`
- Test: `tests/Feature/NewsletterIssueMailTest.php`

**Interfaces:**
- Consumes: `NewsletterIssue`、`NewsletterSubscriber::unsubscribeUrl()`
- Produces: `new NewsletterIssueMail(NewsletterIssue $issue, NewsletterSubscriber $subscriber)`，主旨為期數標題，內文為轉成 HTML 的 Markdown，底部有退訂連結

- [ ] **Step 1: 寫失敗測試**

```php
<?php

use App\Enums\NewsletterTopic;
use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;

function renderedIssueMail(string $body): array
{
    $issue = NewsletterIssue::factory()->create([
        'topic' => NewsletterTopic::Filament,
        'title' => '卡關不是你不熟框架',
        'body' => $body,
    ]);
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    $mail = new NewsletterIssueMail($issue, $subscriber);

    return [$mail, $mail->render(), $subscriber];
}

test('信件主旨是期數標題', function () {
    [$mail] = renderedIssueMail('內文');

    $mail->assertHasSubject('卡關不是你不熟框架');
});

test('Markdown 內文會轉成 HTML', function () {
    [, $html] = renderedIssueMail("## 這是小標\n\n第一段內容");

    expect($html)->toContain('<h2')->toContain('這是小標')->toContain('第一段內容');
});

test('信件底部有該訂閱者的退訂連結', function () {
    [, $html, $subscriber] = renderedIssueMail('內文');

    expect($html)->toContain('/newsletter/unsubscribe')
        ->toContain('signature=')
        ->toContain(e($subscriber->unsubscribeUrl()));
});

test('內文中的 script 與 javascript 連結不會出現在信件中', function () {
    [, $html] = renderedIssueMail("<script>alert(1)</script>\n\n[點我](javascript:alert(1))");

    expect($html)->not->toContain('<script')->not->toContain('javascript:');
});
```

- [ ] **Step 2: 執行測試確認失敗**

Run: `php artisan test --filter=NewsletterIssueMailTest`
Expected: FAIL，找不到 `NewsletterIssueMail`。

- [ ] **Step 3: 建立 Mailable**

`app/Mail/NewsletterIssueMail.php`：

```php
<?php

namespace App\Mail;

use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class NewsletterIssueMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly NewsletterIssue $issue,
        public readonly NewsletterSubscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->issue->title);
    }

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->subscriber->unsubscribeUrl().'>',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.newsletter.issue',
            with: [
                'title' => $this->issue->title,
                'summary' => $this->issue->summary,
                // Written by an admin, but never trusted as HTML: raw HTML and unsafe links are dropped.
                'bodyHtml' => new HtmlString(Str::markdown($this->issue->body, [
                    'html_input' => 'strip',
                    'allow_unsafe_links' => false,
                ])),
                'unsubscribeUrl' => $this->subscriber->unsubscribeUrl(),
            ],
        );
    }
}
```

- [ ] **Step 4: 建立視圖**

`resources/views/mail/newsletter/issue.blade.php`，注意內文不可縮排，否則會被當成程式碼區塊：

```blade
<x-mail::message>
# {{ $title }}

{!! $bodyHtml !!}

---

不想再收到這個主題的電子報，可以 [取消訂閱]({{ $unsubscribeUrl }})。

{{ config('app.name') }}
</x-mail::message>
```

- [ ] **Step 5: 執行測試確認通過**

Run: `php artisan test --filter=NewsletterIssueMailTest`
Expected: PASS，4 個測試。

- [ ] **Step 6: Commit**

```bash
git add app/Mail/NewsletterIssueMail.php resources/views/mail/newsletter/issue.blade.php tests/Feature/NewsletterIssueMailTest.php
git commit -m "Add newsletter issue mail"
```

---

### Task 5: 單一收件者的寄送工作

**Files:**
- Create: `app/Jobs/SendNewsletterIssue.php`
- Test: `tests/Feature/SendNewsletterIssueTest.php`

**Interfaces:**
- Consumes: `NewsletterIssueMail`、`NewsletterSubscriber::deliverable()`、`NewsletterIssue::deliveries()`
- Produces: `new SendNewsletterIssue(int $issueId, int $subscriberId)`，使用 `Batchable`

- [ ] **Step 1: 寫失敗測試**

```php
<?php

use App\Enums\NewsletterTopic;
use App\Jobs\SendNewsletterIssue;
use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->issue = NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament]);
    $this->subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
});

test('寄出信件並寫入寄送紀錄', function () {
    (new SendNewsletterIssue($this->issue->id, $this->subscriber->id))->handle();

    Mail::assertSent(NewsletterIssueMail::class, fn (NewsletterIssueMail $mail) => $mail->hasTo($this->subscriber->email));
    expect(NewsletterDelivery::where('newsletter_issue_id', $this->issue->id)->count())->toBe(1);
});

test('重試時已有寄送紀錄就不再寄送', function () {
    $job = new SendNewsletterIssue($this->issue->id, $this->subscriber->id);

    $job->handle();
    $job->handle();

    Mail::assertSent(NewsletterIssueMail::class, 1);
    expect(NewsletterDelivery::count())->toBe(1);
});

test('寄出前已退訂的訂閱者不會收到信', function () {
    $this->subscriber->update(['unsubscribed_at' => now()]);

    (new SendNewsletterIssue($this->issue->id, $this->subscriber->id))->handle();

    Mail::assertNothingSent();
    expect(NewsletterDelivery::count())->toBe(0);
});

test('期數或訂閱者已被刪除時直接結束', function () {
    (new SendNewsletterIssue(999999, $this->subscriber->id))->handle();
    (new SendNewsletterIssue($this->issue->id, 999999))->handle();

    Mail::assertNothingSent();
});
```

- [ ] **Step 2: 執行測試確認失敗**

Run: `php artisan test --filter=SendNewsletterIssueTest`
Expected: FAIL，找不到 `SendNewsletterIssue`。

- [ ] **Step 3: 建立工作**

`app/Jobs/SendNewsletterIssue.php`：

```php
<?php

namespace App\Jobs;

use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNewsletterIssue implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120, 300];

    public int $tries = 4;

    public function __construct(
        public readonly int $issueId,
        public readonly int $subscriberId,
    ) {}

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $issue = NewsletterIssue::find($this->issueId);
        $subscriber = NewsletterSubscriber::find($this->subscriberId);

        if (! $issue || ! $subscriber) {
            return;
        }

        // A retry after a successful send must not send the same issue twice.
        if ($issue->deliveries()->where('newsletter_subscriber_id', $subscriber->id)->exists()) {
            return;
        }

        // The subscriber may have unsubscribed between queueing and sending.
        if (! NewsletterSubscriber::deliverable()->whereKey($subscriber->id)->exists()) {
            return;
        }

        Mail::to($subscriber->email)->send(new NewsletterIssueMail($issue, $subscriber));

        $issue->deliveries()->create([
            'newsletter_subscriber_id' => $subscriber->id,
            'sent_at' => now(),
        ]);
    }
}
```

- [ ] **Step 4: 執行測試確認通過**

Run: `php artisan test --filter=SendNewsletterIssueTest`
Expected: PASS，4 個測試。

- [ ] **Step 5: Commit**

```bash
git add app/Jobs/SendNewsletterIssue.php tests/Feature/SendNewsletterIssueTest.php
git commit -m "Add job that sends one newsletter issue to one subscriber"
```

---

### Task 6: 排程指令與每分鐘排程

**Files:**
- Create: `app/Console/Commands/SendDueNewsletterIssues.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/SendDueNewsletterIssuesTest.php`

**Interfaces:**
- Consumes: `NewsletterIssue::due()`、`NewsletterIssue::recipients()`、`SendNewsletterIssue`
- Produces: Artisan 指令 `newsletter:send-due`

- [ ] **Step 1: 寫失敗測試**

測試環境 `QUEUE_CONNECTION=sync`，批次會同步執行。

```php
<?php

use App\Enums\NewsletterTopic;
use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
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
    $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'newsletter:send-due'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});
```

- [ ] **Step 2: 執行測試確認失敗**

Run: `php artisan test --filter=SendDueNewsletterIssuesTest`
Expected: FAIL，找不到指令 `newsletter:send-due`。

- [ ] **Step 3: 建立指令**

`app/Console/Commands/SendDueNewsletterIssues.php`：

```php
<?php

namespace App\Console\Commands;

use App\Jobs\SendNewsletterIssue;
use App\Models\NewsletterIssue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SendDueNewsletterIssues extends Command
{
    protected $signature = 'newsletter:send-due';

    protected $description = 'Queue newsletter issues whose scheduled time has passed';

    public function handle(): int
    {
        NewsletterIssue::due()->get()->each(function (NewsletterIssue $issue): void {
            // Only the process that wins this update queues the issue.
            $claimed = NewsletterIssue::query()
                ->whereKey($issue->id)
                ->whereNull('dispatched_at')
                ->update(['dispatched_at' => now()]);

            if ($claimed !== 1) {
                return;
            }

            $jobs = $issue->recipients()
                ->pluck('id')
                ->map(fn (int $subscriberId) => new SendNewsletterIssue($issue->id, $subscriberId))
                ->all();

            if ($jobs === []) {
                $issue->update(['sent_at' => now()]);
                $this->components->info("第 {$issue->number} 期沒有可寄送的訂閱者，直接標記為已寄出");

                return;
            }

            $issueId = $issue->id;

            Bus::batch($jobs)
                ->allowFailures()
                ->finally(function () use ($issueId): void {
                    NewsletterIssue::query()->whereKey($issueId)->update(['sent_at' => now()]);
                })
                ->dispatch();

            $this->components->info('第 '.$issue->number.' 期已派送給 '.count($jobs).' 位訂閱者');
        });

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: 加入排程**

`routes/console.php` 加入：

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('newsletter:send-due')->everyMinute()->withoutOverlapping();
```

- [ ] **Step 5: 執行測試確認通過**

Run: `php artisan test --filter=SendDueNewsletterIssuesTest`
Expected: PASS，5 個測試。

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/SendDueNewsletterIssues.php routes/console.php tests/Feature/SendDueNewsletterIssuesTest.php
git commit -m "Send due newsletter issues every minute"
```

---

### Task 7: 匯入既有期數

**Files:**
- Create: `app/Console/Commands/ImportNewsletterIssues.php`
- Test: `tests/Feature/ImportNewsletterIssuesTest.php`

**Interfaces:**
- Consumes: `NewsletterIssue`、`NewsletterTopic::Filament`
- Produces: Artisan 指令 `newsletter:import-issues {path?}`，預設路徑 `docs/FilamentNewsletter`

- [ ] **Step 1: 寫失敗測試**

```php
<?php

use App\Enums\NewsletterTopic;
use App\Models\NewsletterIssue;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->dir = storage_path('framework/testing/newsletter-import');
    File::deleteDirectory($this->dir);
    File::ensureDirectoryExists($this->dir.'/規格書');

    File::put($this->dir.'/第 01 期：卡關不是你不熟框架.md', <<<'MD'
---
date: 2026-09-22
summary: 這期先建立一套自問流程。
tags:
  - Filament
---

# 卡關不是你不熟框架

第一段內容。

## 小標

第二段內容。
MD);
    File::put($this->dir.'/第 02 期：內建欄位拼不出來的時候.md', "---\nsummary: 第二期摘要\n---\n\n# 內建欄位拼不出來的時候\n\n內文二");
    File::put($this->dir.'/規格書/第 01 期：卡關不是你不熟框架 - 規格書.md', '不應被匯入');
    File::put($this->dir.'/Filament 客製化實戰：12 期電子報規劃.md', '不應被匯入');
});

afterEach(function () {
    File::deleteDirectory($this->dir);
});

test('匯入期號、標題、摘要與去掉標題的內文', function () {
    $this->artisan('newsletter:import-issues', ['path' => $this->dir])->assertSuccessful();

    $issue = NewsletterIssue::where('topic', NewsletterTopic::Filament->value)->where('number', 1)->firstOrFail();

    expect(NewsletterIssue::count())->toBe(2)
        ->and($issue->title)->toBe('卡關不是你不熟框架')
        ->and($issue->summary)->toBe('這期先建立一套自問流程。')
        ->and($issue->body)->toContain('第一段內容。')->toContain('## 小標')
        ->and($issue->body)->not->toContain('# 卡關不是你不熟框架')
        ->and($issue->scheduled_at)->toBeNull();
});

test('重複執行不會產生重複資料', function () {
    $this->artisan('newsletter:import-issues', ['path' => $this->dir])->assertSuccessful();
    $this->artisan('newsletter:import-issues', ['path' => $this->dir])->assertSuccessful();

    expect(NewsletterIssue::count())->toBe(2);
});

test('已派送的期數不會被覆寫', function () {
    NewsletterIssue::factory()->scheduled()->dispatched()->create([
        'topic' => NewsletterTopic::Filament,
        'number' => 1,
        'title' => '已寄出的標題',
        'body' => '已寄出的內文',
    ]);

    $this->artisan('newsletter:import-issues', ['path' => $this->dir])->assertSuccessful();

    $issue = NewsletterIssue::where('number', 1)->firstOrFail();
    expect($issue->title)->toBe('已寄出的標題')->and($issue->body)->toBe('已寄出的內文');
});

test('尚未派送的期數會被更新', function () {
    NewsletterIssue::factory()->create([
        'topic' => NewsletterTopic::Filament,
        'number' => 1,
        'title' => '舊標題',
        'body' => '舊內文',
    ]);

    $this->artisan('newsletter:import-issues', ['path' => $this->dir])->assertSuccessful();

    expect(NewsletterIssue::where('number', 1)->firstOrFail()->title)->toBe('卡關不是你不熟框架');
});

test('路徑不存在時回報錯誤', function () {
    $this->artisan('newsletter:import-issues', ['path' => $this->dir.'/missing'])->assertFailed();
});
```

- [ ] **Step 2: 執行測試確認失敗**

Run: `php artisan test --filter=ImportNewsletterIssuesTest`
Expected: FAIL，找不到指令 `newsletter:import-issues`。

- [ ] **Step 3: 建立指令**

`app/Console/Commands/ImportNewsletterIssues.php`：

```php
<?php

namespace App\Console\Commands;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterIssue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportNewsletterIssues extends Command
{
    protected $signature = 'newsletter:import-issues {path? : Directory of 第 NN 期：標題.md files}';

    protected $description = 'Import Filament newsletter issues from Markdown files';

    public function handle(): int
    {
        $path = $this->argument('path') ?? base_path('docs/FilamentNewsletter');

        if (! File::isDirectory($path)) {
            $this->components->error("找不到目錄：{$path}");

            return self::FAILURE;
        }

        $imported = 0;
        $skipped = 0;

        foreach (File::files($path) as $file) {
            if (! preg_match('/^第 (\d+) 期：(.+)\.md$/u', $file->getFilename(), $matches)) {
                continue;
            }

            [$summary, $body] = $this->parse(File::get($file->getPathname()));

            $number = (int) $matches[1];
            $existing = NewsletterIssue::query()
                ->where('topic', NewsletterTopic::Filament->value)
                ->where('number', $number)
                ->first();

            if ($existing?->isDispatched()) {
                $skipped++;

                continue;
            }

            NewsletterIssue::query()->updateOrCreate(
                ['topic' => NewsletterTopic::Filament->value, 'number' => $number],
                ['title' => $matches[2], 'summary' => $summary, 'body' => $body],
            );

            $imported++;
        }

        $this->components->info("已匯入 {$imported} 期，略過已派送 {$skipped} 期");

        return self::SUCCESS;
    }

    /**
     * Split a file into its front matter summary and the body without the leading title.
     *
     * @return array{0: string|null, 1: string}
     */
    private function parse(string $contents): array
    {
        $summary = null;
        $body = $contents;

        if (preg_match('/\A---\R(.*?)\R---\R?(.*)\z/su', $contents, $parts)) {
            if (preg_match('/^summary:\s*(.+)$/mu', $parts[1], $match)) {
                $summary = trim($match[1]);
            }

            $body = $parts[2];
        }

        $body = preg_replace('/\A\s*#\s+[^\n]*\R/u', '', ltrim($body)) ?? $body;

        return [$summary, Str::of($body)->trim()->toString()];
    }
}
```

- [ ] **Step 4: 執行測試確認通過**

Run: `php artisan test --filter=ImportNewsletterIssuesTest`
Expected: PASS，5 個測試。

- [ ] **Step 5: 對真實文件做乾跑檢查**

Run: `php artisan newsletter:import-issues --no-interaction` 之前，先確認目前資料庫不是正式資料。本機執行後，用 `php artisan tinker --execute='echo App\Models\NewsletterIssue::count();'` 確認為 12。

Expected: `已匯入 12 期，略過已派送 0 期`。

- [ ] **Step 6: Commit**

```bash
git add app/Console/Commands/ImportNewsletterIssues.php tests/Feature/ImportNewsletterIssuesTest.php
git commit -m "Add command to import newsletter issues from Markdown"
```

---

### Task 8: Filament 後台「電子報期數」

**Files:**
- Create: `app/Filament/Resources/NewsletterIssues/NewsletterIssueResource.php`
- Create: `app/Filament/Resources/NewsletterIssues/Schemas/NewsletterIssueForm.php`
- Create: `app/Filament/Resources/NewsletterIssues/Tables/NewsletterIssuesTable.php`
- Create: `app/Filament/Resources/NewsletterIssues/Pages/ListNewsletterIssues.php`
- Create: `app/Filament/Resources/NewsletterIssues/Pages/CreateNewsletterIssue.php`
- Create: `app/Filament/Resources/NewsletterIssues/Pages/EditNewsletterIssue.php`
- Test: `tests/Feature/Filament/NewsletterIssueResourceTest.php`

**Interfaces:**
- Consumes: `NewsletterIssue`、`NewsletterTopic`
- Produces: Resource `NewsletterIssueResource`，頁面 `index`、`create`、`edit`

先用 `php artisan list` 或 Boost 的 `search-docs` 確認 Filament 4 的 `Schema`、`Resource::form()` 簽章，下列程式碼依 Filament 4 撰寫。

- [ ] **Step 1: 寫失敗測試**

```php
<?php

use App\Enums\NewsletterTopic;
use App\Filament\Resources\NewsletterIssues\NewsletterIssueResource;
use App\Filament\Resources\NewsletterIssues\Pages\CreateNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\EditNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\ListNewsletterIssues;
use App\Models\NewsletterIssue;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['email' => 'flamerecca711@gmail.com']));
});

test('管理員可以開啟期數列表', function () {
    $this->get(NewsletterIssueResource::getUrl('index'))->assertOk();
});

test('非管理員無法開啟期數列表', function () {
    $this->actingAs(User::factory()->create());

    $this->get(NewsletterIssueResource::getUrl('index'))->assertForbidden();
});

test('列表顯示期數、主題與狀態', function () {
    $draft = NewsletterIssue::factory()->create(['title' => '草稿期', 'number' => 1]);
    $sent = NewsletterIssue::factory()->scheduled()->sent()->create(['title' => '已寄出期', 'number' => 2]);

    livewire(ListNewsletterIssues::class)
        ->assertCanSeeTableRecords([$draft, $sent])
        ->assertSee('Filament 進階分享')
        ->assertSee('未排程')
        ->assertSee('已寄出');
});

test('可以建立新的期數並設定預計寄送時間', function () {
    livewire(CreateNewsletterIssue::class)
        ->fillForm([
            'topic' => NewsletterTopic::Filament->value,
            'number' => 13,
            'title' => '加開一期',
            'summary' => '摘要',
            'body' => '內文',
            'scheduled_at' => now()->addDay(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(NewsletterIssue::where('number', 13)->first())
        ->title->toBe('加開一期')
        ->scheduled_at->not->toBeNull();
});

test('同一主題的期號不能重複', function () {
    NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);

    livewire(CreateNewsletterIssue::class)
        ->fillForm([
            'topic' => NewsletterTopic::Filament->value,
            'number' => 1,
            'title' => '重複',
            'body' => '內文',
        ])
        ->call('create')
        ->assertHasFormErrors(['number']);
});

test('已派送期數的內文與預計寄送時間唯讀', function () {
    $issue = NewsletterIssue::factory()->scheduled()->dispatched()->create();

    livewire(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->assertFormFieldIsDisabled('body')
        ->assertFormFieldIsDisabled('scheduled_at');
});

test('尚未派送期數的內文可以編輯', function () {
    $issue = NewsletterIssue::factory()->create();

    livewire(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->assertFormFieldIsEnabled('body')
        ->fillForm(['body' => '新的內文'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($issue->refresh()->body)->toBe('新的內文');
});
```

- [ ] **Step 2: 執行測試確認失敗**

Run: `php artisan test --filter=NewsletterIssueResourceTest`
Expected: FAIL，找不到 `NewsletterIssueResource`。

- [ ] **Step 3: 建立 Resource**

`app/Filament/Resources/NewsletterIssues/NewsletterIssueResource.php`：

```php
<?php

namespace App\Filament\Resources\NewsletterIssues;

use App\Filament\Resources\NewsletterIssues\Pages\CreateNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\EditNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\ListNewsletterIssues;
use App\Filament\Resources\NewsletterIssues\Schemas\NewsletterIssueForm;
use App\Filament\Resources\NewsletterIssues\Tables\NewsletterIssuesTable;
use App\Models\NewsletterIssue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NewsletterIssueResource extends Resource
{
    protected static ?string $model = NewsletterIssue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = '電子報期數';

    protected static ?string $pluralModelLabel = '電子報期數';

    public static function form(Schema $schema): Schema
    {
        return NewsletterIssueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NewsletterIssuesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterIssues::route('/'),
            'create' => CreateNewsletterIssue::route('/create'),
            'edit' => EditNewsletterIssue::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 4: 建立表單**

`app/Filament/Resources/NewsletterIssues/Schemas/NewsletterIssueForm.php`：

```php
<?php

namespace App\Filament\Resources\NewsletterIssues\Schemas;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterIssue;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class NewsletterIssueForm
{
    public static function configure(Schema $schema): Schema
    {
        $locked = fn (?NewsletterIssue $record): bool => $record?->isDispatched() ?? false;

        return $schema->components([
            Select::make('topic')
                ->label('電子報')
                ->options(NewsletterTopic::class)
                ->required()
                ->live(),
            TextInput::make('number')
                ->label('期號')
                ->numeric()
                ->minValue(1)
                ->required()
                ->unique(
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('topic', $get('topic')),
                ),
            TextInput::make('title')
                ->label('標題')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Textarea::make('summary')
                ->label('摘要')
                ->rows(2)
                ->columnSpanFull(),
            MarkdownEditor::make('body')
                ->label('內文')
                ->required()
                ->disabled($locked)
                ->columnSpanFull(),
            DateTimePicker::make('scheduled_at')
                ->label('預計寄送時間')
                ->helperText('到了這個時間，系統會自動寄給已確認的訂閱者，留空代表尚未排程。')
                ->disabled($locked),
        ]);
    }
}
```

- [ ] **Step 5: 建立表格**

`app/Filament/Resources/NewsletterIssues/Tables/NewsletterIssuesTable.php`：

```php
<?php

namespace App\Filament\Resources\NewsletterIssues\Tables;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterIssue;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsletterIssuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('number')
            ->columns([
                TextColumn::make('topic')
                    ->label('電子報')
                    ->badge(),
                TextColumn::make('number')
                    ->label('期號')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('標題')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('scheduled_at')
                    ->label('預計寄送時間')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('尚未排程'),
                TextColumn::make('status')
                    ->label('狀態')
                    ->badge()
                    ->state(fn (NewsletterIssue $record): string => $record->status())
                    ->color(fn (string $state): string => match ($state) {
                        '已寄出' => 'success',
                        '派送中' => 'warning',
                        '已排程' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('deliveries_count')
                    ->label('已寄出人數')
                    ->counts('deliveries'),
            ])
            ->filters([
                SelectFilter::make('topic')
                    ->label('電子報')
                    ->options(NewsletterTopic::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (NewsletterIssue $record): bool => ! $record->isDispatched()),
            ]);
    }
}
```

- [ ] **Step 6: 建立頁面**

`Pages/ListNewsletterIssues.php`：

```php
<?php

namespace App\Filament\Resources\NewsletterIssues\Pages;

use App\Filament\Resources\NewsletterIssues\NewsletterIssueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNewsletterIssues extends ListRecords
{
    protected static string $resource = NewsletterIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
```

`Pages/CreateNewsletterIssue.php`：

```php
<?php

namespace App\Filament\Resources\NewsletterIssues\Pages;

use App\Filament\Resources\NewsletterIssues\NewsletterIssueResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewsletterIssue extends CreateRecord
{
    protected static string $resource = NewsletterIssueResource::class;
}
```

`Pages/EditNewsletterIssue.php`：

```php
<?php

namespace App\Filament\Resources\NewsletterIssues\Pages;

use App\Filament\Resources\NewsletterIssues\NewsletterIssueResource;
use Filament\Resources\Pages\EditRecord;

class EditNewsletterIssue extends EditRecord
{
    protected static string $resource = NewsletterIssueResource::class;
}
```

- [ ] **Step 7: 執行測試確認通過**

Run: `php artisan test --filter=NewsletterIssueResourceTest`
Expected: PASS，7 個測試。若 `assertFormFieldIsDisabled` 或 `unique` 的簽章與 Filament 版本不符，依錯誤訊息與 Filament 4 文件調整，不要改測試的行為意圖。

- [ ] **Step 8: Commit**

```bash
git add app/Filament/Resources/NewsletterIssues tests/Feature/Filament/NewsletterIssueResourceTest.php
git commit -m "Add Filament resource for newsletter issues"
```

---

### Task 9: Mailgun 設定說明與整體驗證

**Files:**
- Modify: `.env.example`
- Test: 既有 `tests/Feature/MailgunMailerTest.php`，不修改

- [ ] **Step 1: 補上 `.env.example` 說明**

在 `MAIL_MAILER=log` 那一行之前加入註解，並保留預設值：

```dotenv
# Newsletters are sent through Mailgun in production: set MAIL_MAILER=mailgun
# together with MAILGUN_DOMAIN and MAILGUN_SECRET below.
# The scheduler must be running (php artisan schedule:work, or a cron entry for schedule:run)
# and a queue worker must be running for scheduled newsletters to go out.
MAIL_MAILER=log
```

- [ ] **Step 2: 執行電子報全部測試**

Run: `php artisan test --filter="Newsletter|SendDue|ImportNewsletter|MailgunMailer|HomePage"`
Expected: 全部 PASS。

- [ ] **Step 3: 格式化**

Run: `vendor/bin/pint --dirty`
Expected: `passed`。

- [ ] **Step 4: 手動驗證排程流程**

在本機以 `MAIL_MAILER=log` 驗證，不呼叫 Mailgun：

```bash
php artisan newsletter:import-issues
php artisan tinker --execute='App\Models\NewsletterIssue::where("number",1)->update(["scheduled_at"=>now()->subMinute()]);'
php artisan newsletter:send-due
php artisan queue:work --stop-when-empty
```

Expected: `storage/logs/laravel.log` 出現該期信件，內含退訂連結。後台「電子報期數」列表中第 1 期狀態為「已寄出」。

- [ ] **Step 5: Commit**

```bash
git add .env.example
git commit -m "Document Mailgun and scheduler setup for newsletters"
```
