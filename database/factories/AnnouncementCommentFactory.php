<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnouncementComment>
 */
class AnnouncementCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'announcement_id' => Announcement::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'content' => $this->faker->paragraph(),
        ];
    }
}
