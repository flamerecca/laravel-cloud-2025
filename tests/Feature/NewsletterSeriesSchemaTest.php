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
