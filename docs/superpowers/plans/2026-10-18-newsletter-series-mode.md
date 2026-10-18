# 電子報系列模式 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 部署之後才確認信箱的主題訂閱者進入系列模式，依個人進度與可設定的間隔，從第 1 期一路收到最新一期，管理員可在後台調整下一封。

**Architecture:** 訂閱者新增三個欄位記錄系列模式、下次可寄時間與起始期號。進度不另存，由 `newsletter_deliveries` 推得。新指令 `newsletter:send-series` 每分鐘找出該寄的訂閱者，以條件式更新搶佔後派送既有的 `SendNewsletterIssue`。廣播名單排除系列模式訂閱者。

**Tech Stack:** Laravel 13、Filament 5、Livewire 4、Pest、SQLite 測試資料庫。

**Spec:** `docs/superpowers/specs/2026-10-18-newsletter-series-mode-design.md`

## Global Constraints

- 所有面向使用者的文字使用台灣正體中文，中英文之間留半形空格，不用括號補充說明。
- 新檔案一律用 `php artisan make:` 指令建立，並加上 `--no-interaction`。
- 不在 config 以外使用 `env()`。
- 每個變更都要有 Pest 測試，執行最少必要的測試即可。
- 完成每個 Task 前執行 `vendor/bin/pint --dirty`。
- 預設間隔為 7 天，最小為 1 天。
- `series_start_number` 預設 1。
- 已發布的定義：`scheduled_at` 不為空且 `scheduled_at <= now()`。
- 一般電子報，也就是 `topic` 為 null 的訂閱者，沒有任何期數，不進入系列模式。
- 工作分支為 `feature/newsletter-series-mode`，不推送，不合併。

## Review Focus

- 主題下沒有任何已發布期數時，指令不派送，也不推後 `series_next_at`，下一分鐘仍會檢查。Task 4 測試。
- 指令連續執行兩次，同一位訂閱者只收到一封。Task 4 測試。
- 期數已被廣播流程標記為已派送或已寄出，系列模式訂閱者仍要能收到。Task 4 測試。
- 退訂後重新訂閱，不會重寄已收過的期數。Task 2 與 Task 4 測試。
- 一般電子報訂閱者確認後不進入系列模式。Task 2 測試。

## 已知後果，不在本次處理

廣播流程在「該主題沒有廣播模式訂閱者」時，會把到期的期數直接標記為已派送與已寄出，這會鎖住該期的內容編輯。對只有系列模式訂閱者的主題，每一期到期後都會如此。需要改內容時，管理員用既有的「改為未寄出」解鎖。這個行為在規格書沒有涵蓋，實作完成後要回報給使用者決定是否另開工作。

## File Structure

- Create: `database/migrations/*_add_series_columns_to_newsletter_subscribers_table.php`，三個新欄位與索引。
- Create: `database/migrations/*_create_newsletter_topic_settings_table.php`，各電子報的間隔天數。
- Create: `app/Models/NewsletterTopicSetting.php` 與其 factory，間隔設定與預設值。
- Modify: `app/Models/NewsletterSubscriber.php`，`confirm()`、系列模式查詢範圍、`nextIssue()`、列表用的標籤方法。
- Modify: `app/Models/NewsletterIssue.php`，`published` 範圍，`recipients()` 排除系列模式。
- Modify: `database/factories/NewsletterSubscriberFactory.php`，`inSeries()` 狀態。
- Modify: `app/Jobs/StoreNewsletterSubscriber.php`，重設時清空系列欄位。
- Modify: `app/Http/Controllers/NewsletterConfirmationController.php` 與 `app/Filament/Resources/NewsletterSubscribers/Tables/NewsletterSubscribersTable.php`，改用 `confirm()`。
- Create: `app/Console/Commands/SendSeriesNewsletterIssues.php`，指令本體。
- Modify: `routes/console.php`，註冊排程。
- Create: `app/Filament/Resources/NewsletterTopicSettings/*`，間隔設定的後台資源。
- Modify: `app/Filament/Resources/NewsletterSubscribers/Tables/NewsletterSubscribersTable.php`，進度欄、下一封欄、調整下一封動作。
- Tests: `tests/Feature/NewsletterSeriesSchemaTest.php`、`NewsletterSeriesConfirmationTest.php`、`NewsletterSeriesProgressTest.php`、`SendSeriesNewsletterIssuesTest.php`，以及 `tests/Feature/Filament/NewsletterTopicSettingResourceTest.php`，並追加到 `NewsletterSubscriberResourceTest.php`。

---

### Task 1: 資料表結構、間隔設定模型與 factory 狀態

**Files:**
- Create: `database/migrations/*_add_series_columns_to_newsletter_subscribers_table.php`
- Create: `database/migrations/*_create_newsletter_topic_settings_table.php`
- Create: `app/Models/NewsletterTopicSetting.php`、`database/factories/NewsletterTopicSettingFactory.php`
- Modify: `app/Models/NewsletterSubscriber.php`、`database/factories/NewsletterSubscriberFactory.php`
- Test: `tests/Feature/NewsletterSeriesSchemaTest.php`

**Interfaces:**
- Produces: 訂閱者屬性 `series_started_at`、`series_next_at`、`series_start_number`。`NewsletterSubscriber::isInSeries(): bool`。`NewsletterTopicSetting::DEFAULT_INTERVAL_DAYS = 7`。`NewsletterTopicSetting::intervalDaysFor(NewsletterTopic $topic): int`。`NewsletterSubscriberFactory::inSeries(?Carbon $nextAt = null): static`。

- [ ] **Step 1: 建立檔案**

```bash
php artisan make:migration add_series_columns_to_newsletter_subscribers_table --table=newsletter_subscribers --no-interaction
php artisan make:model NewsletterTopicSetting -mf --no-interaction
php artisan make:test NewsletterSeriesSchemaTest --pest --no-interaction
```

- [ ] **Step 2: 寫失敗的測試**

`tests/Feature/NewsletterSeriesSchemaTest.php`

```php
<?php

use App\Enums\NewsletterTopic;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterTopicSetting;

test('訂閱者預設不在系列模式，起始期號為 1', function () {
    $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

    expect($subscriber->refresh()->isInSeries())->toBeFalse()
        ->and($subscriber->series_next_at)->toBeNull()
        ->and($subscriber->series_start_number)->toBe(1);
});

test('inSeries 狀態讓訂閱者進入系列模式，下次可寄時間預設為現在', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries()->create();

    expect($subscriber->isInSeries())->toBeTrue()
        ->and($subscriber->series_next_at)->not->toBeNull();
});

test('沒有設定時間隔為 7 天，有設定時使用設定值', function () {
    expect(NewsletterTopicSetting::intervalDaysFor(NewsletterTopic::Filament))->toBe(7);

    NewsletterTopicSetting::factory()->create(['topic' => NewsletterTopic::Filament, 'interval_days' => 3]);

    expect(NewsletterTopicSetting::intervalDaysFor(NewsletterTopic::Filament))->toBe(3)
        ->and(NewsletterTopicSetting::intervalDaysFor(NewsletterTopic::Ktor))->toBe(7);
});
```

- [ ] **Step 3: 執行測試確認失敗**

Run: `php artisan test tests/Feature/NewsletterSeriesSchemaTest.php`
Expected: FAIL，例如 `isInSeries` 或欄位不存在。

- [ ] **Step 4: 寫遷移**

訂閱者遷移的 `up` 與 `down`：

```php
public function up(): void
{
    Schema::table('newsletter_subscribers', function (Blueprint $table) {
        $table->timestamp('series_started_at')->nullable()->after('unsubscribed_at');
        $table->timestamp('series_next_at')->nullable()->after('series_started_at');
        // The next issue is only picked from issue numbers at or above this value.
        $table->unsignedInteger('series_start_number')->default(1)->after('series_next_at');

        $table->index(['series_started_at', 'series_next_at']);
    });
}

public function down(): void
{
    Schema::table('newsletter_subscribers', function (Blueprint $table) {
        $table->dropIndex(['series_started_at', 'series_next_at']);
        $table->dropColumn(['series_started_at', 'series_next_at', 'series_start_number']);
    });
}
```

間隔設定遷移的 `up`：

```php
public function up(): void
{
    Schema::create('newsletter_topic_settings', function (Blueprint $table) {
        $table->id();
        $table->string('topic')->unique();
        $table->unsignedSmallInteger('interval_days')->default(7);
        $table->timestamps();
    });
}
```

- [ ] **Step 5: 寫模型與 factory**

`app/Models/NewsletterTopicSetting.php`

```php
<?php

namespace App\Models;

use App\Enums\NewsletterTopic;
use Database\Factories\NewsletterTopicSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** @use HasFactory<NewsletterTopicSettingFactory> */
class NewsletterTopicSetting extends Model
{
    use HasFactory;

    public const DEFAULT_INTERVAL_DAYS = 7;

    protected $fillable = [
        'topic',
        'interval_days',
    ];

    protected function casts(): array
    {
        return [
            'topic' => NewsletterTopic::class,
            'interval_days' => 'integer',
        ];
    }

    /**
     * Days between two issues for a series subscriber of the given topic.
     */
    public static function intervalDaysFor(NewsletterTopic $topic): int
    {
        return static::query()->where('topic', $topic->value)->value('interval_days') ?? self::DEFAULT_INTERVAL_DAYS;
    }

    /**
     * Make sure every topic has a row, so the admin list shows all of them.
     */
    public static function syncWithTopics(): void
    {
        foreach (NewsletterTopic::cases() as $topic) {
            static::query()->firstOrCreate(
                ['topic' => $topic->value],
                ['interval_days' => self::DEFAULT_INTERVAL_DAYS],
            );
        }
    }
}
```

`NewsletterTopicSettingFactory::definition()` 回傳：

```php
return [
    'topic' => NewsletterTopic::Filament,
    'interval_days' => NewsletterTopicSetting::DEFAULT_INTERVAL_DAYS,
];
```

並補上 `use App\Enums\NewsletterTopic;` 與 `use App\Models\NewsletterTopicSetting;`。

`NewsletterSubscriber`：`$fillable` 加入 `'series_started_at'`、`'series_next_at'`、`'series_start_number'`。`casts()` 加入：

```php
'series_started_at' => 'datetime',
'series_next_at' => 'datetime',
'series_start_number' => 'integer',
```

並新增：

```php
public function isInSeries(): bool
{
    return $this->series_started_at !== null;
}
```

`NewsletterSubscriberFactory` 加入，並補 `use Illuminate\Support\Carbon;`：

```php
/**
 * The subscriber receives the topic as a series, one issue per interval.
 */
public function inSeries(?Carbon $nextAt = null): static
{
    return $this->state(fn (array $attributes) => [
        'series_started_at' => now(),
        'series_next_at' => $nextAt ?? now(),
    ]);
}
```

- [ ] **Step 6: 執行測試確認通過**

Run: `php artisan migrate --no-interaction && php artisan test tests/Feature/NewsletterSeriesSchemaTest.php`
Expected: PASS，3 個測試。

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty
git add -A database app tests
git commit -m "Add the series mode columns and per-topic interval settings"
```

---

### Task 2: 確認訂閱時進入系列模式

**Files:**
- Modify: `app/Models/NewsletterSubscriber.php`
- Modify: `app/Http/Controllers/NewsletterConfirmationController.php:32`
- Modify: `app/Jobs/StoreNewsletterSubscriber.php`
- Modify: `app/Filament/Resources/NewsletterSubscribers/Tables/NewsletterSubscribersTable.php`
- Test: `tests/Feature/NewsletterSeriesConfirmationTest.php`

**Interfaces:**
- Consumes: Task 1 的欄位與 `isInSeries()`。
- Produces: `NewsletterSubscriber::confirm(): void`。

- [ ] **Step 1: 建立測試檔**

Run: `php artisan make:test NewsletterSeriesConfirmationTest --pest --no-interaction`

- [ ] **Step 2: 寫失敗的測試**

`tests/Feature/NewsletterSeriesConfirmationTest.php`

```php
<?php

use App\Enums\NewsletterTopic;
use App\Jobs\StoreNewsletterSubscriber;
use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Mail::fake();
});

test('confirm 讓主題訂閱者進入系列模式並馬上可以寄第一封', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();

    $subscriber->confirm();

    $subscriber->refresh();
    expect($subscriber->isConfirmed())->toBeTrue()
        ->and($subscriber->isInSeries())->toBeTrue()
        ->and($subscriber->series_next_at->lte(now()))->toBeTrue();
});

test('一般電子報訂閱者確認後不進入系列模式', function () {
    $subscriber = NewsletterSubscriber::factory()->create();

    $subscriber->confirm();

    expect($subscriber->refresh()->isConfirmed())->toBeTrue()
        ->and($subscriber->isInSeries())->toBeFalse();
});

test('已在系列模式的訂閱者再次 confirm 不會重設下次可寄時間', function () {
    $nextAt = now()->addDays(3)->startOfSecond();
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries($nextAt)->create();

    $subscriber->confirm();

    expect($subscriber->refresh()->series_next_at->equalTo($nextAt))->toBeTrue();
});

test('信件中的確認連結讓訂閱者進入系列模式', function () {
    StoreNewsletterSubscriber::dispatchSync('reader@example.com', now(), NewsletterTopic::Filament);
    $url = Mail::sent(NewsletterConfirmation::class)->first()->confirmUrl();

    $this->get($url)->assertOk();

    expect(NewsletterSubscriber::where('email', 'reader@example.com')->first()->isInSeries())->toBeTrue();
});

test('退訂後重新訂閱並重新確認，仍然進入系列模式且保留管理員調整的起始期號', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries()->unsubscribed()->create(['series_start_number' => 4]);

    StoreNewsletterSubscriber::dispatchSync($subscriber->email, now(), NewsletterTopic::Filament);

    $subscriber->refresh();
    expect($subscriber->confirmed_at)->toBeNull()
        ->and($subscriber->unsubscribed_at)->toBeNull()
        ->and($subscriber->isInSeries())->toBeFalse();

    $subscriber->confirm();

    expect($subscriber->refresh()->isInSeries())->toBeTrue()
        ->and($subscriber->series_start_number)->toBe(4);
});

test('後台直接確認訂閱與批次確認都讓主題訂閱者進入系列模式', function () {
    $this->actingAs(User::factory()->create(['email' => 'flamerecca711@gmail.com']));
    $single = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();
    $bulk = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Ktor)->create();

    livewire(\App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers::class)
        ->callAction(TestAction::make('confirmSubscription')->table($single));

    livewire(\App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers::class)
        ->selectTableRecords([$bulk->id])
        ->callAction(TestAction::make('confirmSubscriptions')->table()->bulk());

    expect($single->refresh()->isInSeries())->toBeTrue()
        ->and($bulk->refresh()->isInSeries())->toBeTrue();
});
```

- [ ] **Step 3: 執行測試確認失敗**

Run: `php artisan test tests/Feature/NewsletterSeriesConfirmationTest.php`
Expected: FAIL，`confirm()` 不存在。

- [ ] **Step 4: 實作**

`NewsletterSubscriber` 新增：

```php
/**
 * Mark the address as confirmed. Topic subscribers who confirm start the series from the beginning,
 * with the first issue due right away. The general newsletter has no issues, so it stays out.
 */
public function confirm(): void
{
    $attributes = ['confirmed_at' => now(), 'unsubscribed_at' => null];

    if ($this->topic !== null && ! $this->isInSeries()) {
        $attributes += ['series_started_at' => now(), 'series_next_at' => now()];
    }

    $this->update($attributes);
}
```

`NewsletterConfirmationController` 把 `$subscriber->update(['confirmed_at' => now()]);` 換成 `$subscriber->confirm();`。

`StoreNewsletterSubscriber` 中退訂後回來的 `update` 加入兩個欄位：

```php
$subscriber->update([
    'subscribed_at' => $this->subscribedAt,
    'confirmed_at' => null,
    'unsubscribed_at' => null,
    'series_started_at' => null,
    'series_next_at' => null,
]);
```

`NewsletterSubscribersTable` 的單筆動作內 `$record->update(['confirmed_at' => now(), 'unsubscribed_at' => null]);` 換成 `$record->confirm();`，批次動作的 `each(fn (...) => $record->update([...]))` 換成 `each(fn (NewsletterSubscriber $record) => $record->confirm())`。

- [ ] **Step 5: 執行測試確認通過**

Run: `php artisan test tests/Feature/NewsletterSeriesConfirmationTest.php tests/Feature/NewsletterSubscriptionTest.php tests/Feature/Filament/NewsletterSubscriberResourceTest.php`
Expected: PASS，既有測試不受影響。

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty
git add -A app tests
git commit -m "Start the series when a topic subscriber confirms"
```

---

### Task 3: 進度判定與廣播名單隔離

**Files:**
- Modify: `app/Models/NewsletterIssue.php`
- Modify: `app/Models/NewsletterSubscriber.php`
- Test: `tests/Feature/NewsletterSeriesProgressTest.php`

**Interfaces:**
- Consumes: Task 1 的欄位。
- Produces: `NewsletterIssue::scopePublished()`、`NewsletterSubscriber::scopeInSeries()`、`scopeSeriesDue()`、`nextIssue(): ?NewsletterIssue`、`seriesProgress(): string`、`nextIssueLabel(): string`。`NewsletterIssue::recipients()` 排除系列模式。

- [ ] **Step 1: 建立測試檔**

Run: `php artisan make:test NewsletterSeriesProgressTest --pest --no-interaction`

- [ ] **Step 2: 寫失敗的測試**

`tests/Feature/NewsletterSeriesProgressTest.php`

```php
<?php

use App\Enums\NewsletterTopic;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;

function seriesSubscriber(array $attributes = []): NewsletterSubscriber
{
    return NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries()->create($attributes);
}

function publishedIssue(int $number, array $attributes = []): NewsletterIssue
{
    return NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament, 'number' => $number, ...$attributes]);
}

function deliver(NewsletterIssue $issue, NewsletterSubscriber $subscriber, int $round = 1): void
{
    NewsletterDelivery::create([
        'newsletter_issue_id' => $issue->id,
        'newsletter_subscriber_id' => $subscriber->id,
        'send_round' => $round,
        'sent_at' => now(),
    ]);
}

test('下一封是已發布且還沒收過的最小期號', function () {
    $subscriber = seriesSubscriber();
    $first = publishedIssue(1);
    $second = publishedIssue(2);

    expect($subscriber->nextIssue()->is($first))->toBeTrue();

    deliver($first, $subscriber);

    expect($subscriber->nextIssue()->is($second))->toBeTrue();
});

test('草稿、未來才發布與其他主題的期數都不算', function () {
    $subscriber = seriesSubscriber();
    NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);
    publishedIssue(2, ['scheduled_at' => now()->addDay()]);
    NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Ktor, 'number' => 3]);

    expect($subscriber->nextIssue())->toBeNull();
});

test('起始期號之前的期數會被略過', function () {
    $subscriber = seriesSubscriber(['series_start_number' => 3]);
    publishedIssue(1);
    publishedIssue(2);
    $third = publishedIssue(3);

    expect($subscriber->nextIssue()->is($third))->toBeTrue();
});

test('舊回合的寄送紀錄不算已收到這一期', function () {
    $subscriber = seriesSubscriber();
    $issue = publishedIssue(1, ['send_round' => 2]);
    deliver($issue, $subscriber, round: 1);

    expect($subscriber->nextIssue()->is($issue))->toBeTrue();

    deliver($issue, $subscriber, round: 2);

    expect($subscriber->nextIssue())->toBeNull();
});

test('廣播名單排除系列模式訂閱者', function () {
    $issue = publishedIssue(1);
    $broadcast = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    seriesSubscriber();

    expect($issue->recipients()->pluck('id')->all())->toBe([$broadcast->id]);
});

test('進度與下一封的標籤', function () {
    $subscriber = seriesSubscriber();
    $first = publishedIssue(1);
    publishedIssue(2);
    $broadcast = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    expect($subscriber->seriesProgress())->toBe('已收到 0 / 2 期')
        ->and($subscriber->nextIssueLabel())->toBe('第 1 期')
        ->and($broadcast->seriesProgress())->toBe('廣播')
        ->and($broadcast->nextIssueLabel())->toBe('廣播');

    deliver($first, $subscriber);
    deliver(NewsletterIssue::where('number', 2)->first(), $subscriber);

    expect($subscriber->seriesProgress())->toBe('已收到 2 / 2 期')
        ->and($subscriber->nextIssueLabel())->toBe('已追上');
});

test('seriesDue 只包含已確認、未退訂且時間已到的系列模式訂閱者', function () {
    $due = seriesSubscriber();
    seriesSubscriber(['series_next_at' => now()->addHour()]);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries()->unsubscribed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->inSeries()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    expect(NewsletterSubscriber::seriesDue()->pluck('id')->all())->toBe([$due->id]);
});
```

- [ ] **Step 3: 執行測試確認失敗**

Run: `php artisan test tests/Feature/NewsletterSeriesProgressTest.php`
Expected: FAIL，方法不存在。

- [ ] **Step 4: 實作**

`NewsletterIssue` 新增範圍，並修改 `recipients()`：

```php
/**
 * Issues whose scheduled time has passed, whether or not the broadcast has been queued.
 *
 * @param  Builder<static>  $query
 */
public function scopePublished(Builder $query): void
{
    $query->whereNotNull('scheduled_at')->where('scheduled_at', '<=', now());
}

/**
 * The broadcast subscribers this issue goes to. Series subscribers get issues one by one instead.
 *
 * @return Builder<NewsletterSubscriber>
 */
public function recipients(): Builder
{
    return NewsletterSubscriber::forTopic($this->topic)->deliverable()->whereNull('series_started_at');
}
```

`NewsletterSubscriber` 新增，並補 `use Illuminate\Database\Eloquent\Relations\...` 不需要，只需引用同命名空間的 `NewsletterIssue`：

```php
/**
 * Subscribers who receive the topic as a series.
 *
 * @param  Builder<static>  $query
 */
public function scopeInSeries(Builder $query): void
{
    $query->whereNotNull('series_started_at');
}

/**
 * Series subscribers whose next issue may go out now.
 *
 * @param  Builder<static>  $query
 */
public function scopeSeriesDue(Builder $query): void
{
    $query->deliverable()->inSeries()->where('series_next_at', '<=', now());
}

/**
 * The lowest published issue at or above the start number that this subscriber has not received in its current round.
 */
public function nextIssue(): ?NewsletterIssue
{
    if ($this->topic === null) {
        return null;
    }

    return NewsletterIssue::query()
        ->published()
        ->where('topic', $this->topic->value)
        ->where('number', '>=', $this->series_start_number)
        ->whereDoesntHave('deliveries', fn (Builder $query) => $query
            ->where('newsletter_subscriber_id', $this->id)
            ->whereColumn('newsletter_deliveries.send_round', 'newsletter_issues.send_round'))
        ->orderBy('number')
        ->first();
}

public function seriesProgress(): string
{
    if (! $this->isInSeries() || $this->topic === null) {
        return '廣播';
    }

    $published = NewsletterIssue::query()->published()->where('topic', $this->topic->value);

    $received = (clone $published)
        ->whereHas('deliveries', fn (Builder $query) => $query
            ->where('newsletter_subscriber_id', $this->id)
            ->whereColumn('newsletter_deliveries.send_round', 'newsletter_issues.send_round'))
        ->count();

    return "已收到 {$received} / {$published->count()} 期";
}

public function nextIssueLabel(): string
{
    if (! $this->isInSeries()) {
        return '廣播';
    }

    $issue = $this->nextIssue();

    return $issue ? "第 {$issue->number} 期" : '已追上';
}
```

- [ ] **Step 5: 執行測試確認通過**

Run: `php artisan test tests/Feature/NewsletterSeriesProgressTest.php tests/Feature/SendDueNewsletterIssuesTest.php tests/Feature/NewsletterIssueModelTest.php`
Expected: PASS，既有廣播測試不受影響。

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty
git add -A app tests
git commit -m "Work out each series subscriber's next issue and keep them out of the broadcast"
```

---

### Task 4: 系列寄送指令與排程

**Files:**
- Create: `app/Console/Commands/SendSeriesNewsletterIssues.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/SendSeriesNewsletterIssuesTest.php`

**Interfaces:**
- Consumes: Task 3 的 `seriesDue()`、`nextIssue()`，Task 1 的 `intervalDaysFor()`。既有的 `SendNewsletterIssue(int $issueId, int $subscriberId, ?int $round)`。
- Produces: 指令 `newsletter:send-series`。

- [ ] **Step 1: 建立指令與測試檔**

```bash
php artisan make:command SendSeriesNewsletterIssues --command=newsletter:send-series --no-interaction
php artisan make:test SendSeriesNewsletterIssuesTest --pest --no-interaction
```

- [ ] **Step 2: 寫失敗的測試**

`tests/Feature/SendSeriesNewsletterIssuesTest.php`

```php
<?php

use App\Enums\NewsletterTopic;
use App\Mail\NewsletterIssueMail;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterTopicSetting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

function seriesReader(): NewsletterSubscriber
{
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();
    $subscriber->confirm();

    return $subscriber->refresh();
}

function issueNumber(int $number): NewsletterIssue
{
    return NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament, 'number' => $number]);
}

test('新確認的訂閱者馬上收到第 1 期，之後每隔一個間隔收到下一期', function () {
    issueNumber(1);
    issueNumber(2);
    issueNumber(3);
    $reader = seriesReader();

    $this->artisan('newsletter:send-series')->assertSuccessful();
    Mail::assertSent(NewsletterIssueMail::class, 1);
    Mail::assertSent(NewsletterIssueMail::class, fn ($mail) => $mail->hasTo($reader->email) && $mail->issue->number === 1);

    $this->travel(6)->days();
    $this->artisan('newsletter:send-series')->assertSuccessful();
    Mail::assertSent(NewsletterIssueMail::class, 1);

    $this->travel(1)->days();
    $this->artisan('newsletter:send-series')->assertSuccessful();
    Mail::assertSent(NewsletterIssueMail::class, 2);
    Mail::assertSent(NewsletterIssueMail::class, fn ($mail) => $mail->issue->number === 2);
});

test('連續執行兩次，同一位訂閱者只收到一封', function () {
    issueNumber(1);
    issueNumber(2);
    seriesReader();

    $this->artisan('newsletter:send-series')->assertSuccessful();
    $this->artisan('newsletter:send-series')->assertSuccessful();

    Mail::assertSent(NewsletterIssueMail::class, 1);
});

test('主題下沒有已發布的期數時不派送，也不推後下次可寄時間', function () {
    NewsletterIssue::factory()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);
    $reader = seriesReader();
    $before = $reader->series_next_at;

    $this->artisan('newsletter:send-series')->assertSuccessful();

    Mail::assertNothingSent();
    expect($reader->refresh()->series_next_at->equalTo($before))->toBeTrue();
});

test('追上進度後不再寄，新期數發布後下一次執行就寄出', function () {
    issueNumber(1);
    $reader = seriesReader();
    $this->artisan('newsletter:send-series');
    Mail::assertSent(NewsletterIssueMail::class, 1);

    $this->travel(8)->days();
    $this->artisan('newsletter:send-series');
    Mail::assertSent(NewsletterIssueMail::class, 1);

    issueNumber(2);
    $this->artisan('newsletter:send-series');
    Mail::assertSent(NewsletterIssueMail::class, 2);
});

test('已被廣播流程標記為已寄出的期數，系列模式訂閱者仍然收得到', function () {
    NewsletterIssue::factory()->scheduled()->sent()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);
    seriesReader();

    $this->artisan('newsletter:send-series')->assertSuccessful();

    Mail::assertSent(NewsletterIssueMail::class, 1);
});

test('間隔依各電子報的設定', function () {
    NewsletterTopicSetting::factory()->create(['topic' => NewsletterTopic::Filament, 'interval_days' => 2]);
    issueNumber(1);
    issueNumber(2);
    seriesReader();

    $this->artisan('newsletter:send-series');
    $this->travel(2)->days();
    $this->artisan('newsletter:send-series');

    Mail::assertSent(NewsletterIssueMail::class, 2);
});

test('退訂、尚未確認與廣播模式的訂閱者不會被系列指令寄送', function () {
    issueNumber(1);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries()->unsubscribed()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->inSeries()->create();
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    $this->artisan('newsletter:send-series')->assertSuccessful();

    Mail::assertNothingSent();
});

test('退訂後重新訂閱不會重寄已收過的期數', function () {
    issueNumber(1);
    issueNumber(2);
    $reader = seriesReader();
    $this->artisan('newsletter:send-series');
    Mail::assertSent(NewsletterIssueMail::class, 1);

    $reader->update(['unsubscribed_at' => now()]);
    \App\Jobs\StoreNewsletterSubscriber::dispatchSync($reader->email, now(), NewsletterTopic::Filament);
    $reader->refresh()->confirm();

    $this->artisan('newsletter:send-series');

    Mail::assertSent(NewsletterIssueMail::class, 2);
    Mail::assertSent(NewsletterIssueMail::class, fn ($mail) => $mail->issue->number === 2);
});

test('排程器每分鐘執行 newsletter:send-series', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains($event->command, 'newsletter:send-series'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(10);
});
```

- [ ] **Step 3: 執行測試確認失敗**

Run: `php artisan test tests/Feature/SendSeriesNewsletterIssuesTest.php`
Expected: FAIL，指令沒有實作。

- [ ] **Step 4: 實作指令**

`app/Console/Commands/SendSeriesNewsletterIssues.php`

```php
<?php

namespace App\Console\Commands;

use App\Jobs\SendNewsletterIssue;
use App\Models\NewsletterSubscriber;
use App\Models\NewsletterTopicSetting;
use Illuminate\Console\Command;

class SendSeriesNewsletterIssues extends Command
{
    protected $signature = 'newsletter:send-series';

    protected $description = 'Queue the next issue for every series subscriber whose turn has come';

    public function handle(): int
    {
        $queued = 0;

        // By id, not by offset: claiming a subscriber changes the query's own filter.
        foreach (NewsletterSubscriber::query()->seriesDue()->lazyById() as $subscriber) {
            $issue = $subscriber->nextIssue();

            if (! $issue) {
                continue;
            }

            // Only the process that wins this update queues the issue for this subscriber.
            $claimed = NewsletterSubscriber::query()
                ->whereKey($subscriber->id)
                ->where('series_next_at', '<=', now())
                ->update(['series_next_at' => now()->addDays(NewsletterTopicSetting::intervalDaysFor($subscriber->topic))]);

            if ($claimed !== 1) {
                continue;
            }

            SendNewsletterIssue::dispatch($issue->id, $subscriber->id, $issue->send_round);
            $queued++;
        }

        $this->components->info("已派送 {$queued} 封系列電子報");

        return self::SUCCESS;
    }
}
```

`routes/console.php` 加在 `newsletter:send-due` 後面：

```php
Schedule::command('newsletter:send-series')->everyMinute()->withoutOverlapping(10);
```

- [ ] **Step 5: 執行測試確認通過**

Run: `php artisan test tests/Feature/SendSeriesNewsletterIssuesTest.php`
Expected: PASS，9 個測試。

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty
git add -A app routes tests
git commit -m "Send each series subscriber the next issue on their own schedule"
```

---

### Task 5: 後台間隔設定

**Files:**
- Create: `app/Filament/Resources/NewsletterTopicSettings/NewsletterTopicSettingResource.php`
- Create: `app/Filament/Resources/NewsletterTopicSettings/Pages/ListNewsletterTopicSettings.php`
- Create: `app/Filament/Resources/NewsletterTopicSettings/Tables/NewsletterTopicSettingsTable.php`
- Test: `tests/Feature/Filament/NewsletterTopicSettingResourceTest.php`

**Interfaces:**
- Consumes: Task 1 的 `NewsletterTopicSetting::syncWithTopics()`。

- [ ] **Step 1: 產生資源骨架並清理**

```bash
php artisan make:filament-resource NewsletterTopicSetting --no-interaction
php artisan make:test Filament/NewsletterTopicSettingResourceTest --pest --no-interaction
```

刪除自動產生的 `Pages/CreateNewsletterTopicSetting.php`、`Pages/EditNewsletterTopicSetting.php` 與 `Schemas/` 目錄，再依下面內容覆寫其餘檔案。

- [ ] **Step 2: 寫失敗的測試**

`tests/Feature/Filament/NewsletterTopicSettingResourceTest.php`

```php
<?php

use App\Enums\NewsletterTopic;
use App\Filament\Resources\NewsletterTopicSettings\NewsletterTopicSettingResource;
use App\Filament\Resources\NewsletterTopicSettings\Pages\ListNewsletterTopicSettings;
use App\Models\NewsletterTopicSetting;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['email' => 'flamerecca711@gmail.com']));
});

test('管理員可以開啟間隔設定，非管理員不行', function () {
    $this->get(NewsletterTopicSettingResource::getUrl('index'))->assertOk();

    $this->actingAs(User::factory()->create());

    $this->get(NewsletterTopicSettingResource::getUrl('index'))->assertForbidden();
});

test('開啟列表時每個電子報都有一筆設定，預設 7 天', function () {
    livewire(ListNewsletterTopicSettings::class);

    expect(NewsletterTopicSetting::count())->toBe(count(NewsletterTopic::cases()))
        ->and(NewsletterTopicSetting::pluck('interval_days')->unique()->all())->toBe([7]);
});

test('可以直接在列表修改間隔天數', function () {
    NewsletterTopicSetting::syncWithTopics();
    $setting = NewsletterTopicSetting::where('topic', NewsletterTopic::Filament->value)->first();

    livewire(ListNewsletterTopicSettings::class)
        ->updateTableColumnState('interval_days', $setting->getKey(), 14);

    expect($setting->refresh()->interval_days)->toBe(14)
        ->and(NewsletterTopicSetting::intervalDaysFor(NewsletterTopic::Filament))->toBe(14);
});

test('間隔天數不能小於 1', function () {
    NewsletterTopicSetting::syncWithTopics();
    $setting = NewsletterTopicSetting::first();

    livewire(ListNewsletterTopicSettings::class)
        ->updateTableColumnState('interval_days', $setting->getKey(), 0);

    expect($setting->refresh()->interval_days)->toBe(7);
});
```

- [ ] **Step 3: 執行測試確認失敗**

Run: `php artisan test tests/Feature/Filament/NewsletterTopicSettingResourceTest.php`
Expected: FAIL。

- [ ] **Step 4: 實作**

`NewsletterTopicSettingResource.php`

```php
<?php

namespace App\Filament\Resources\NewsletterTopicSettings;

use App\Filament\Resources\NewsletterTopicSettings\Pages\ListNewsletterTopicSettings;
use App\Filament\Resources\NewsletterTopicSettings\Tables\NewsletterTopicSettingsTable;
use App\Models\NewsletterTopicSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NewsletterTopicSettingResource extends Resource
{
    protected static ?string $model = NewsletterTopicSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $modelLabel = '系列寄送間隔';

    protected static ?string $pluralModelLabel = '系列寄送間隔';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return NewsletterTopicSettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsletterTopicSettings::route('/'),
        ];
    }
}
```

`Pages/ListNewsletterTopicSettings.php`

```php
<?php

namespace App\Filament\Resources\NewsletterTopicSettings\Pages;

use App\Filament\Resources\NewsletterTopicSettings\NewsletterTopicSettingResource;
use App\Models\NewsletterTopicSetting;
use Filament\Resources\Pages\ListRecords;

class ListNewsletterTopicSettings extends ListRecords
{
    protected static string $resource = NewsletterTopicSettingResource::class;

    public function mount(): void
    {
        NewsletterTopicSetting::syncWithTopics();

        parent::mount();
    }
}
```

`Tables/NewsletterTopicSettingsTable.php`

```php
<?php

namespace App\Filament\Resources\NewsletterTopicSettings\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

class NewsletterTopicSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('topic')
                    ->label('電子報')
                    ->badge(),
                TextInputColumn::make('interval_days')
                    ->label('每幾天寄一封')
                    ->type('number')
                    ->rules(['required', 'integer', 'min:1']),
            ]);
    }
}
```

- [ ] **Step 5: 執行測試確認通過**

Run: `php artisan test tests/Feature/Filament/NewsletterTopicSettingResourceTest.php`
Expected: PASS，4 個測試。若 `updateTableColumnState` 在這個 Filament 版本的名稱不同，用 `search-docs` 查 table column 的測試寫法後調整測試，不要改實作。

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty
git add -A app tests
git commit -m "Let admins set the series interval per newsletter"
```

---

### Task 6: 訂閱者列表的進度、下一封與調整動作

**Files:**
- Modify: `app/Filament/Resources/NewsletterSubscribers/Tables/NewsletterSubscribersTable.php`
- Test: `tests/Feature/Filament/NewsletterSubscriberResourceTest.php`（追加）

**Interfaces:**
- Consumes: Task 3 的 `seriesProgress()`、`nextIssueLabel()`，Task 1 的 `series_start_number`。

- [ ] **Step 1: 寫失敗的測試**

追加到 `tests/Feature/Filament/NewsletterSubscriberResourceTest.php`，並在檔案頂端補 `use App\Models\NewsletterIssue;`：

```php
test('列表顯示系列模式訂閱者的進度與下一封，廣播模式顯示廣播', function () {
    NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);
    $series = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries()->create();
    $broadcast = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    livewire(ListNewsletterSubscribers::class)
        ->assertCanSeeTableRecords([$series, $broadcast])
        ->assertSee('已收到 0 / 1 期')
        ->assertSee('第 1 期')
        ->assertSee('廣播');
});

test('可以調整系列模式訂閱者的下一封，被略過的期數不會再寄', function () {
    $first = NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);
    $third = NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament, 'number' => 3]);
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries()->create();

    livewire(ListNewsletterSubscribers::class)
        ->callAction(TestAction::make('adjustNextIssue')->table($subscriber), ['number' => $third->number])
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect($subscriber->refresh()->series_start_number)->toBe(3)
        ->and($subscriber->nextIssue()->is($third))->toBeTrue();
});

test('調整下一封只能選該訂閱者主題下的期數', function () {
    NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Ktor, 'number' => 9]);
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->inSeries()->create();

    livewire(ListNewsletterSubscribers::class)
        ->callAction(TestAction::make('adjustNextIssue')->table($subscriber), ['number' => 9])
        ->assertHasActionErrors(['number']);

    expect($subscriber->refresh()->series_start_number)->toBe(1);
});

test('廣播模式訂閱者不顯示調整下一封', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    livewire(ListNewsletterSubscribers::class)
        ->assertActionHidden(TestAction::make('adjustNextIssue')->table($subscriber));
});
```

- [ ] **Step 2: 執行測試確認失敗**

Run: `php artisan test tests/Feature/Filament/NewsletterSubscriberResourceTest.php --filter="下一封|進度"`
Expected: FAIL。

- [ ] **Step 3: 實作**

`NewsletterSubscribersTable.php` 補 imports：`use App\Models\NewsletterIssue;`、`use Filament\Forms\Components\Select;`。

在 `confirmed_at` 欄位後加入兩欄：

```php
TextColumn::make('series_progress')
    ->label('進度')
    ->state(fn (NewsletterSubscriber $record): string => $record->seriesProgress()),
TextColumn::make('next_issue')
    ->label('下一封')
    ->state(fn (NewsletterSubscriber $record): string => $record->nextIssueLabel()),
```

在 `recordActions` 的 `confirmSubscription` 動作之後、`DeleteAction::make()` 之前加入：

```php
Action::make('adjustNextIssue')
    ->label('調整下一封')
    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
    ->visible(fn (NewsletterSubscriber $record): bool => $record->isInSeries())
    ->modalDescription('設定之後，下一封只會從所選的期數開始找。所選期數之前還沒收到的期數會被略過，已經收過的期數不會重寄。寄送時間仍依原本的間隔。')
    ->fillForm(fn (NewsletterSubscriber $record): array => ['number' => $record->series_start_number])
    ->schema(fn (NewsletterSubscriber $record): array => [
        Select::make('number')
            ->label('下一封從哪一期開始')
            ->options(fn (): array => NewsletterIssue::query()
                ->where('topic', $record->topic->value)
                ->orderBy('number')
                ->get()
                ->mapWithKeys(fn (NewsletterIssue $issue): array => [$issue->number => "第 {$issue->number} 期：{$issue->title}"])
                ->all())
            ->required(),
    ])
    ->action(function (NewsletterSubscriber $record, array $data): void {
        $record->update(['series_start_number' => (int) $data['number']]);

        Notification::make()
            ->title("{$record->email} 的下一封已調整為第 {$data['number']} 期起")
            ->success()
            ->send();
    }),
```

- [ ] **Step 4: 執行測試確認通過**

Run: `php artisan test tests/Feature/Filament/NewsletterSubscriberResourceTest.php`
Expected: PASS，包含既有測試。

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint --dirty
git add -A app tests
git commit -m "Show series progress in the subscriber list and let admins adjust the next issue"
```

---

### Task 7: 整體驗證

- [ ] **Step 1: 跑全部測試**

Run: `vendor/bin/pint --dirty && php artisan test`
Expected: 全部通過。

- [ ] **Step 2: 手動確認排程註冊**

Run: `php artisan schedule:list`
Expected: 看得到 `newsletter:send-due` 與 `newsletter:send-series`，都是每分鐘。

- [ ] **Step 3: 回報給使用者**

回報完成狀態，並明確說明「已知後果，不在本次處理」那一段，請使用者決定是否另開工作。不推送，不合併，等使用者指示。
