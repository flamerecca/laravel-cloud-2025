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

test('--topic 指定要匯入到哪個主題', function () {
    $this->artisan('newsletter:import-issues', ['path' => $this->dir, '--topic' => 'fastapi'])->assertSuccessful();

    expect(NewsletterIssue::where('topic', NewsletterTopic::FastApi->value)->count())->toBe(2)
        ->and(NewsletterIssue::where('topic', NewsletterTopic::Filament->value)->count())->toBe(0);
});

test('不同主題可以有相同的期號', function () {
    $this->artisan('newsletter:import-issues', ['path' => $this->dir])->assertSuccessful();
    $this->artisan('newsletter:import-issues', ['path' => $this->dir, '--topic' => 'ktor'])->assertSuccessful();

    expect(NewsletterIssue::count())->toBe(4);
});

test('用底線取代空格的檔名也能匯入，標題的底線會還原成空格', function () {
    File::deleteDirectory($this->dir);
    File::ensureDirectoryExists($this->dir);
    File::put($this->dir.'/第_05_期：Depends_一下，共用邏輯就不用到處複製.md', "---\nsummary: 摘要\n---\n\n# Depends 一下\n\n內文");

    $this->artisan('newsletter:import-issues', ['path' => $this->dir, '--topic' => 'fastapi'])->assertSuccessful();

    $issue = NewsletterIssue::where('topic', NewsletterTopic::FastApi->value)->where('number', 5)->firstOrFail();

    expect($issue->title)->toBe('Depends 一下，共用邏輯就不用到處複製');
});

test('未知的主題會失敗', function () {
    $this->artisan('newsletter:import-issues', ['path' => $this->dir, '--topic' => 'nope'])->assertFailed();

    expect(NewsletterIssue::count())->toBe(0);
});

test('沒有預設目錄的主題必須指定 path', function () {
    $this->artisan('newsletter:import-issues', ['--topic' => 'laravel'])->assertFailed();
});

test('不指定 path 時依主題讀取 docs 底下的預設目錄', function () {
    $this->artisan('newsletter:import-issues', ['--topic' => 'fastapi'])->assertSuccessful();

    expect(NewsletterIssue::where('topic', NewsletterTopic::FastApi->value)->count())->toBe(12);
})->skip(fn () => ! is_dir(base_path('docs/Newsletter/FastAPINewsletter')), 'docs/Newsletter/FastAPINewsletter is not in this checkout');
