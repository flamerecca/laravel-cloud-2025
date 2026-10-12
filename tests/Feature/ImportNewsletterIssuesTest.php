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
