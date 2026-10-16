<?php

use App\Enums\NewsletterTopic;
use App\Filament\Resources\NewsletterIssues\NewsletterIssueResource;
use App\Filament\Resources\NewsletterIssues\Pages\CreateNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\EditNewsletterIssue;
use App\Filament\Resources\NewsletterIssues\Pages\ListNewsletterIssues;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Filament\Actions\Testing\TestAction;

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

test('已派送期數的所有內容欄位唯讀', function () {
    $issue = NewsletterIssue::factory()->scheduled()->dispatched()->create();

    livewire(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()])
        ->assertFormFieldIsDisabled('topic')
        ->assertFormFieldIsDisabled('number')
        ->assertFormFieldIsDisabled('title')
        ->assertFormFieldIsDisabled('summary')
        ->assertFormFieldIsDisabled('body')
        ->assertFormFieldIsDisabled('scheduled_at');
});

test('開啟編輯頁後期數才被派送，儲存不會改動已派送的內容', function () {
    $issue = NewsletterIssue::factory()->create(['title' => '原標題', 'body' => '原內文']);

    $page = livewire(EditNewsletterIssue::class, ['record' => $issue->getRouteKey()]);

    $issue->update(['scheduled_at' => now()->subMinute(), 'dispatched_at' => now()]);

    $page->fillForm(['title' => '新標題', 'body' => '新內文'])->call('save');

    $issue->refresh();
    expect($issue->title)->toBe('原標題')->and($issue->body)->toBe('原內文');
});

test('預計寄送時間以台北時間輸入並以 UTC 儲存', function () {
    livewire(CreateNewsletterIssue::class)
        ->fillForm([
            'topic' => NewsletterTopic::Filament->value,
            'number' => 20,
            'title' => '時區',
            'body' => '內文',
            'scheduled_at' => '2026-10-13 09:00:00',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $issue = NewsletterIssue::where('number', 20)->firstOrFail();

    expect($issue->scheduled_at->utc()->format('Y-m-d H:i'))->toBe('2026-10-13 01:00');
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

test('已派送的期數可以改為未寄出，並保留舊的寄送紀錄', function () {
    $issue = NewsletterIssue::factory()->scheduled()->sent()->create();
    $subscriber = NewsletterSubscriber::factory()->confirmed()->create();
    $issue->deliveries()->create(['newsletter_subscriber_id' => $subscriber->id, 'sent_at' => now()]);

    livewire(ListNewsletterIssues::class)
        ->callAction(TestAction::make('markUnsent')->table($issue))
        ->assertNotified();

    $issue->refresh();
    expect($issue->status())->toBe('未排程')
        ->and($issue->send_round)->toBe(2)
        ->and($issue->deliveries()->count())->toBe(1);
});

test('尚未派送的期數不顯示改為未寄出', function () {
    $issue = NewsletterIssue::factory()->scheduled()->create();

    livewire(ListNewsletterIssues::class)
        ->assertActionHidden(TestAction::make('markUnsent')->table($issue));
});

test('卡在派送中的期數也可以改為未寄出', function () {
    $issue = NewsletterIssue::factory()->scheduled()->dispatched()->create();

    livewire(ListNewsletterIssues::class)
        ->assertActionVisible(TestAction::make('markUnsent')->table($issue));
});

test('已寄出人數只計算目前這一輪', function () {
    $issue = NewsletterIssue::factory()->create();
    $subscribers = NewsletterSubscriber::factory()->count(3)->confirmed()->create();
    foreach ($subscribers as $subscriber) {
        $issue->deliveries()->create(['newsletter_subscriber_id' => $subscriber->id, 'send_round' => 1, 'sent_at' => now()]);
    }
    $issue->resetToUnsent();
    $issue->deliveries()->create(['newsletter_subscriber_id' => $subscribers->first()->id, 'send_round' => 2, 'sent_at' => now()]);

    livewire(ListNewsletterIssues::class)
        ->assertTableColumnStateSet('deliveries_count', 1, $issue);
});

test('可以直接在列表頁編輯預計寄送時間', function () {
    $issue = NewsletterIssue::factory()->create(['scheduled_at' => null]);

    livewire(ListNewsletterIssues::class)
        ->callAction(TestAction::make('editScheduledAt')->table($issue), ['scheduled_at' => '2026-10-13 09:00:00'])
        ->assertHasNoFormErrors();

    expect($issue->refresh()->scheduled_at->utc()->format('Y-m-d H:i'))->toBe('2026-10-13 01:00');
});

test('可以在列表頁清空預計寄送時間', function () {
    $issue = NewsletterIssue::factory()->scheduled()->create();

    livewire(ListNewsletterIssues::class)
        ->callAction(TestAction::make('editScheduledAt')->table($issue), ['scheduled_at' => null]);

    expect($issue->refresh()->scheduled_at)->toBeNull();
});

test('已派送的期數無法在列表頁編輯預計寄送時間', function () {
    $issue = NewsletterIssue::factory()->scheduled()->sent()->create();

    livewire(ListNewsletterIssues::class)
        ->assertActionHidden(TestAction::make('editScheduledAt')->table($issue));
});
