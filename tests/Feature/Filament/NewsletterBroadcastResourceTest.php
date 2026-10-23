<?php

use App\Enums\NewsletterTopic;
use App\Filament\Resources\NewsletterBroadcasts\NewsletterBroadcastResource;
use App\Filament\Resources\NewsletterBroadcasts\Pages\CreateNewsletterBroadcast;
use App\Filament\Resources\NewsletterBroadcasts\Pages\EditNewsletterBroadcast;
use App\Filament\Resources\NewsletterBroadcasts\Pages\ListNewsletterBroadcasts;
use App\Mail\NewsletterBroadcastMail;
use App\Models\NewsletterBroadcast;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Mail::fake();
    $this->actingAs(User::factory()->create(['email' => 'flamerecca711@gmail.com']));
});

test('非管理員無法開啟公告列表', function () {
    $this->actingAs(User::factory()->create());

    $this->get(NewsletterBroadcastResource::getUrl('index'))->assertForbidden();
});

test('可以建立公告', function () {
    livewire(CreateNewsletterBroadcast::class)
        ->fillForm(['title' => '站務公告', 'summary' => '摘要', 'body' => '內文', 'audiences' => ['ktor']])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(NewsletterBroadcast::query()->where('title', '站務公告')->first()->audiences)->toBe(['ktor']);
});

test('建立公告時至少要勾選一個收件對象', function () {
    livewire(CreateNewsletterBroadcast::class)
        ->fillForm(['title' => '站務公告', 'body' => '內文', 'audiences' => []])
        ->call('create')
        ->assertHasFormErrors(['audiences']);
});

test('列表上的寄送按鈕寄給去重後的所有訂閱者', function () {
    $broadcast = NewsletterBroadcast::factory()->create();
    NewsletterSubscriber::factory()->confirmed()->create(['email' => 'a@example.com']);
    NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Ktor)->confirmed()->create(['email' => 'a@example.com']);

    livewire(ListNewsletterBroadcasts::class)
        ->callAction(TestAction::make('send')->table($broadcast))
        ->assertNotified();

    Mail::assertSent(NewsletterBroadcastMail::class, 1);
    expect($broadcast->fresh()->isDispatched())->toBeTrue();
});

test('已寄送的公告看不到寄送按鈕', function () {
    $broadcast = NewsletterBroadcast::factory()->sent()->create();

    livewire(ListNewsletterBroadcasts::class)
        ->assertActionHidden(TestAction::make('send')->table($broadcast));
});

test('已開始派送的公告不能再儲存變更', function () {
    $broadcast = NewsletterBroadcast::factory()->create(['title' => '原標題']);
    $page = livewire(EditNewsletterBroadcast::class, ['record' => $broadcast->getKey()]);

    $broadcast->update(['dispatched_at' => now()]);

    $page->fillForm(['title' => '新標題'])->call('save');

    expect($broadcast->fresh()->title)->toBe('原標題');
});
