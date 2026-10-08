<?php

namespace Database\Factories;

use App\Enums\NewsletterTopic;
use App\Models\NewsletterSubscriber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterSubscriber>
 */
class NewsletterSubscriberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'subscribed_at' => now(),
        ];
    }

    /**
     * The subscriber has clicked the confirmation link.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'confirmed_at' => now(),
        ]);
    }

    /**
     * The subscriber signed up for the given topic instead of the general newsletter.
     */
    public function forTopic(NewsletterTopic $topic): static
    {
        return $this->state(fn (array $attributes) => [
            'topic' => $topic,
        ]);
    }
}
