<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $announcements = Announcement::all();
        $users = User::all();

        if ($announcements->isEmpty() || $users->isEmpty()) {
            return;
        }

        $announcements->each(function ($announcement) use ($users) {
            // 為每個公告建立 3-5 個主評論
            AnnouncementComment::factory(rand(3, 5))->create([
                'announcement_id' => $announcement->id,
                'user_id' => $users->random()->id,
            ])->each(function ($comment) use ($announcement, $users) {
                // 為每個主評論隨機建立 0-2 個回覆
                if (rand(0, 1)) {
                    AnnouncementComment::factory(rand(1, 2))->create([
                        'announcement_id' => $announcement->id,
                        'user_id' => $users->random()->id,
                        'parent_id' => $comment->id,
                    ]);
                }
            });
        });
    }
}
