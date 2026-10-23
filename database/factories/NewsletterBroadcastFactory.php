<?php

namespace Database\Factories;

use App\Models\NewsletterBroadcast;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterBroadcast>
 */
class NewsletterBroadcastFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'summary' => $this->faker->sentence(),
            'body' => "## {$this->faker->sentence(3)}\n\n{$this->faker->paragraph()}",
        ];
    }

    /**
     * @param  array<int, string>  $audiences
     */
    public function forAudiences(array $audiences): static
    {
        return $this->state(fn (array $attributes) => ['audiences' => $audiences]);
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
