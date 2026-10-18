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
        ->call('updateTableColumnState', 'interval_days', (string) $setting->getKey(), 14);

    expect($setting->refresh()->interval_days)->toBe(14)
        ->and(NewsletterTopicSetting::intervalDaysFor(NewsletterTopic::Filament))->toBe(14);
});

test('間隔天數不能小於 1', function () {
    NewsletterTopicSetting::syncWithTopics();
    $setting = NewsletterTopicSetting::first();

    livewire(ListNewsletterTopicSettings::class)
        ->call('updateTableColumnState', 'interval_days', (string) $setting->getKey(), 0);

    expect($setting->refresh()->interval_days)->toBe(7);
});
