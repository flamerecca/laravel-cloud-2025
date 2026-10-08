<?php

use App\Enums\NewsletterTopic;
use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Filament\Resources\NewsletterSubscribers\Tables\NewsletterSubscribersTable;
use App\Jobs\StoreNewsletterSubscriber;
use App\Mail\NewsletterConfirmation;
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
