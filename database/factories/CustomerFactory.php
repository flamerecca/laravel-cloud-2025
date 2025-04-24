<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake(locale: 'zh_TW')->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '09' . rand(10000000, 99999999),
        ];
    }
}
