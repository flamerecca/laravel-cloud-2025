<?php

use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('可以為公告建立評論', function () {
    $announcement = Announcement::factory()->create();
    $user = User::factory()->create();

    $comment = AnnouncementComment::create([
        'announcement_id' => $announcement->id,
        'user_id' => $user->id,
        'content' => '這是一條測試評論',
    ]);

    expect($comment->content)->toBe('這是一條測試評論');
    expect($comment->announcement->id)->toBe($announcement->id);
    expect($comment->user->id)->toBe($user->id);
});

test('評論支援軟刪除', function () {
    $comment = AnnouncementComment::factory()->create();

    $comment->delete();

    expect($comment->trashed())->toBeTrue();
    $this->assertSoftDeleted('announcement_comments', [
        'id' => $comment->id,
    ]);
});

test('評論可以擁有回覆', function () {
    $parentComment = AnnouncementComment::factory()->create();

    $reply = AnnouncementComment::factory()->create([
        'parent_id' => $parentComment->id,
        'announcement_id' => $parentComment->announcement_id,
    ]);

    expect($parentComment->replies)->toHaveCount(1);
    expect($parentComment->replies->first()->id)->toBe($reply->id);
    expect($reply->parent->id)->toBe($parentComment->id);
});

test('刪除公告時會連帶刪除評論', function () {
    $announcement = Announcement::factory()->create();
    AnnouncementComment::factory(3)->create([
        'announcement_id' => $announcement->id,
    ]);

    expect(AnnouncementComment::count())->toBe(3);

    $announcement->delete();

    // 注意：如果在 Migration 中使用的是 constrained()->cascadeOnDelete()，
    // 對於 SoftDeletes 的 Model，單純 delete() 可能不會觸發資料庫層級的 Cascade Delete，
    // 或者只會標記 Announcement 為已刪除。
    // 這裡我們驗證業務邏輯上的關聯。
    // 如果 Announcement 使用 SoftDeletes，其評論應該還在，除非我們實作了事件監聽或使用的是硬刪除。
    // 根據 Migration：$table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
    // 這是在資料庫硬刪除時生效。

    // 讓我們測試硬刪除以驗證 Cascade
    $announcement->forceDelete();
    expect(AnnouncementComment::count())->toBe(0);
});
