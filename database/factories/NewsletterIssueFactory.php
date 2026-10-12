<?php

namespace Database\Factories;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterIssue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<NewsletterIssue>
 */
class NewsletterIssueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'topic' => NewsletterTopic::Filament,
            'number' => $this->faker->unique()->numberBetween(1, 1000),
            'title' => $this->faker->sentence(4),
            'summary' => $this->faker->sentence(),
            'body' => "## {$this->faker->sentence(3)}\n\n{$this->faker->paragraph()}",
        ];
    }

    public function scheduled(?Carbon $at = null): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => $at ?? now()->subMinute(),
        ]);
    }

    public function dispatched(): static
    {
        return $this->state(fn (array $attributes) => [
            'dispatched_at' => now(),
        ]);
    }

    public function sent(): static
    {
        return $this->dispatched()->state(fn (array $attributes) => [
            'sent_at' => now(),
        ]);
    }
}
