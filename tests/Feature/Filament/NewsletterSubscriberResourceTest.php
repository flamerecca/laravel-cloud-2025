<?php

use App\Enums\NewsletterTopic;
use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Filament\Resources\NewsletterSubscribers\Tables\NewsletterSubscribersTable;
use App\Jobs\StoreNewsletterSubscriber;
use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterIssue;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create(['email' => 'flamerecca711@gmail.com']));
});

test('管理員可以開啟電子報訂閱者列表', function () {
    $this->get(NewsletterSubscriberResource::getUrl('index'))->assertOk();
});

test('非管理員無法開啟電子報訂閱者列表', function () {
    $this->actingAs(User::factory()->create());

    $this->get(NewsletterSubscriberResource::getUrl('index'))->assertForbidden();
});

test('列表顯示一般與主題電子報的所有訂閱者', function () {
    $general = NewsletterSubscriber::factory()->create();
    $topic = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();

    livewire(ListNewsletterSubscribers::class)
        ->assertCanSeeTableRecords([$general, $topic])
        ->assertSee('一般電子報')
        ->assertSee('Filament 進階分享')
        ->assertSee('尚未確認');
});

test('可以搜尋 Email', function () {
    $subscribers = NewsletterSubscriber::factory()->count(3)->create();

    livewire(ListNewsletterSubscribers::class)
        ->searchTable($subscribers->first()->email)
        ->assertCanSeeTableRecords($subscribers->take(1))
        ->assertCanNotSeeTableRecords($subscribers->skip(1));
});

test('可以依電子報主題篩選，包含一般電子報', function () {
    $general = NewsletterSubscriber::factory()->create();
    $topic = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();

    livewire(ListNewsletterSubscribers::class)
        ->filterTable('topic', NewsletterSubscribersTable::GENERAL)
        ->assertCanSeeTableRecords([$general])
        ->assertCanNotSeeTableRecords([$topic])
        ->filterTable('topic', NewsletterTopic::Filament->value)
        ->assertCanSeeTableRecords([$topic])
        ->assertCanNotSeeTableRecords([$general]);
});

test('可以依確認狀態篩選', function () {
    $confirmed = NewsletterSubscriber::factory()->confirmed()->create();
    $pending = NewsletterSubscriber::factory()->create();

    livewire(ListNewsletterSubscribers::class)
        ->filterTable('confirmed_at', true)
        ->assertCanSeeTableRecords([$confirmed])
        ->assertCanNotSeeTableRecords([$pending])
        ->filterTable('confirmed_at', false)
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$confirmed]);
});

test('分頁標籤分別列出尚未確認、已確認與已退訂的訂閱者', function () {
    $pending = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();
    $confirmed = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->create();
    $unsubscribed = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->confirmed()->unsubscribed()->create();

    livewire(ListNewsletterSubscribers::class)
        ->assertCanSeeTableRecords([$pending, $confirmed, $unsubscribed])
        ->set('activeTab', 'pending')
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$confirmed, $unsubscribed])
        ->set('activeTab', 'confirmed')
        ->assertCanSeeTableRecords([$confirmed])
        ->assertCanNotSeeTableRecords([$pending, $unsubscribed])
        ->set('activeTab', 'unsubscribed')
        ->assertCanSeeTableRecords([$unsubscribed])
        ->assertCanNotSeeTableRecords([$pending, $confirmed]);
});

test('分頁標籤的徽章顯示各狀態的人數', function () {
    NewsletterSubscriber::factory()->count(2)->create();
    NewsletterSubscriber::factory()->count(3)->confirmed()->create();
    NewsletterSubscriber::factory()->unsubscribed()->create();

    $tabs = livewire(ListNewsletterSubscribers::class)->instance()->getTabs();

    expect((int) $tabs['all']->getBadge())->toBe(6)
        ->and((int) $tabs['pending']->getBadge())->toBe(2)
        ->and((int) $tabs['confirmed']->getBadge())->toBe(3)
        ->and((int) $tabs['unsubscribed']->getBadge())->toBe(1);
});

test('狀態欄顯示尚未確認、已確認與已退訂', function () {
    $pending = NewsletterSubscriber::factory()->create();
    $confirmed = NewsletterSubscriber::factory()->confirmed()->create();
    $unsubscribed = NewsletterSubscriber::factory()->confirmed()->unsubscribed()->create();

    livewire(ListNewsletterSubscribers::class)
        ->assertTableColumnStateSet('status', '尚未確認', $pending)
        ->assertTableColumnStateSet('status', '已確認', $confirmed)
        ->assertTableColumnStateSet('status', '已退訂', $unsubscribed);
});

test('可以對尚未確認的訂閱者重寄確認信', function () {
    $subscriber = NewsletterSubscriber::factory()->forTopic(NewsletterTopic::Filament)->create();
    Mail::fake();

    livewire(ListNewsletterSubscribers::class)
        ->callAction(TestAction::make('resendConfirmation')->table($subscriber))
        ->assertNotified();

    Mail::assertSent(NewsletterConfirmation::class, fn (NewsletterConfirmation $mail) => $mail->hasTo($subscriber->email)
        && $mail->topic === NewsletterTopic::Filament);
});

test('已確認的訂閱者不顯示重寄確認信', function () {
    $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

    livewire(ListNewsletterSubscribers::class)
        ->assertActionHidden(TestAction::make('resendConfirmation')->table($subscriber));
});

test('後台可以直接確認訂閱，不寄確認信', function () {
    Mail::fake();
    $subscriber = NewsletterSubscriber::factory()->create(['confirmed_at' => null]);

    livewire(ListNewsletterSubscribers::class)
        ->callAction(TestAction::make('confirmSubscription')->table($subscriber))
        ->assertNotified();

    expect($subscriber->refresh()->isConfirmed())->toBeTrue()
        ->and(NewsletterSubscriber::deliverable()->whereKey($subscriber->id)->exists())->toBeTrue();
    Mail::assertNothingSent();
});

test('已確認的訂閱者不顯示直接確認訂閱', function () {
    $subscriber = NewsletterSubscriber::factory()->confirmed()->create();

    livewire(ListNewsletterSubscribers::class)
        ->assertActionHidden(TestAction::make('confirmSubscription')->table($subscriber));
});

test('批次直接確認訂閱只影響尚未確認的訂閱者', function () {
    $pending = NewsletterSubscriber::factory()->count(2)->create(['confirmed_at' => null]);
    $confirmedAt = now()->subDays(3)->startOfSecond();
    $confirmed = NewsletterSubscriber::factory()->create(['confirmed_at' => $confirmedAt]);

    livewire(ListNewsletterSubscribers::class)
        ->selectTableRecords($pending->push($confirmed)->pluck('id')->all())
        ->callAction(TestAction::make('confirmSubscriptions')->table()->bulk())
        ->assertNotified();

    expect(NewsletterSubscriber::whereNull('confirmed_at')->count())->toBe(0)
        ->and($confirmed->refresh()->confirmed_at->equalTo($confirmedAt))->toBeTrue();
});

test('新增訂閱者會走確認信流程，不會直接成為已確認', function () {
    Mail::fake();

    livewire(ListNewsletterSubscribers::class)
        ->callAction('addSubscriber', ['email' => 'new@example.com', 'topic' => NewsletterTopic::Filament->value])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $subscriber = NewsletterSubscriber::forTopic(NewsletterTopic::Filament)->where('email', 'new@example.com')->first();

    expect($subscriber)->not->toBeNull()
        ->and($subscriber->isConfirmed())->toBeFalse();

    Mail::assertSent(NewsletterConfirmation::class, fn (NewsletterConfirmation $mail) => $mail->hasTo('new@example.com'));
});

test('新增訂閱者未選電子報時為一般電子報', function () {
    Queue::fake();

    livewire(ListNewsletterSubscribers::class)
        ->callAction('addSubscriber', ['email' => 'new@example.com'])
        ->assertHasNoActionErrors();

    Queue::assertPushed(StoreNewsletterSubscriber::class, fn (StoreNewsletterSubscriber $job) => $job->email === 'new@example.com' && $job->topic === null);
});

test('新增訂閱者需要有效的 Email', function () {
    Queue::fake();

    livewire(ListNewsletterSubscribers::class)
        ->callAction('addSubscriber', ['email' => 'not-an-email'])
        ->assertHasActionErrors(['email' => 'email']);

    Queue::assertNothingPushed();
});

test('可以刪除訂閱者', function () {
    $subscriber = NewsletterSubscriber::factory()->create();

    livewire(ListNewsletterSubscribers::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($subscriber));

    $this->assertModelMissing($subscriber);
});

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
    NewsletterIssue::factory()->scheduled()->create(['topic' => NewsletterTopic::Filament, 'number' => 1]);
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
