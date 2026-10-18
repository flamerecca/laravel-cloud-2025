<?php

use App\Enums\NewsletterTopic;
use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
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

    livewire(ListNewsletterSubscribers::class)
        ->callAction(TestAction::make('confirmSubscription')->table($single));

    livewire(ListNewsletterSubscribers::class)
        ->selectTableRecords([$bulk->id])
        ->callAction(TestAction::make('confirmSubscriptions')->table()->bulk());

    expect($single->refresh()->isInSeries())->toBeTrue()
        ->and($bulk->refresh()->isInSeries())->toBeTrue();
});
