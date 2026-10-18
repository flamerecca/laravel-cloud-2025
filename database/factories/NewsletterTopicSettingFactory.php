<?php

namespace Database\Factories;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterTopicSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterTopicSetting>
 */
class NewsletterTopicSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic' => NewsletterTopic::Filament,
            'interval_days' => NewsletterTopicSetting::DEFAULT_INTERVAL_DAYS,
        ];
    }
}
