<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\ShortUrl;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShortUrl>
 */
class ShortUrlFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'original_url' => fake()->url(),
            'slug' => Str::random(10),
            'customer_id' => Customer::first()->id,
        ];
    }
}
