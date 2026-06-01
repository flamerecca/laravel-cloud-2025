<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Announcement::create([
            'user_id' => 1,
            'title' => '測試公告標題',
            'content' => '測試公告內容',
            'status' => 1,
            'published_at' => now(),
            'is_pinned' => true,
        ]);

    }
}
