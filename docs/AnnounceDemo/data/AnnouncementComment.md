# 公告評論系統 (AnnouncementComment) 文件

## 相關文件
- [公告系統模型文件](./Announcement.md)

## 簡介
本文件介紹了公告評論系統的資料結構與模型設計。此功能允許用戶在公告下發表評論。

## 資料庫結構 (Database Schema)

### `announcement_comments` 資料表

| 欄位名稱              | 型別          | 說明                  |
|-------------------|-------------|---------------------|
| `id`              | BigInt (PK) | 自動增量 ID             |
| `announcement_id` | BigInt (FK) | 關聯的公告 ID            |
| `user_id`         | String      | 發表評論的用戶 ID          |
| `content`         | Text        | 評論內容                |
| `created_at`      | Timestamp   | 建立時間                |
| `updated_at`      | Timestamp   | 更新時間                |
| `deleted_at`      | Timestamp   | 軟刪除時間 (Soft Delete) |

## 模型說明 (Model)

### `App\Models\AnnouncementComment`

模型使用了以下特性：
- `HasFactory`: 支援工廠模式進行資料填充。
- `SoftDeletes`: 支援軟刪除功能。

#### 關聯設計 (Relationships)
- `announcement()`: 屬於一個公告 (`BelongsTo`)。
- `user()`: 屬於一個用戶 (`BelongsTo`)。
- `replies()`: 擁有多個子評論 (`HasMany`)。
- `parent()`: 屬於一個父評論 (`BelongsTo`)。

## 快速開始

### 1. 建立遷移與模型 (建議指令)
```bash
php artisan make:model AnnouncementComment -m
```

### 2. 基本查詢範例
```php
// 取得特定公告的所有評論（包含用戶資訊）
$comments = AnnouncementComment::where('announcement_id', $announcementId)
    ->with('user')
    ->latest()
    ->get();
```

## 測試案例 (Testing)

以下是使用 Pest 撰寫的測試範例，用於驗證評論模型的行為。

### 1. 建立評論測試
```php
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
```

### 2. 軟刪除測試
```php
test('評論支援軟刪除', function () {
    $comment = AnnouncementComment::factory()->create();

    $comment->delete();

    expect($comment->trashed())->toBeTrue();
    $this->assertSoftDeleted('announcement_comments', [
        'id' => $comment->id,
    ]);
});
```

### 3. 回覆功能測試 (父子評論)
```php
test('評論可以擁有回覆', function () {
    $parentComment = AnnouncementComment::factory()->create();
    
    $reply = AnnouncementComment::factory()->create([
        'parent_id' => $parentComment->id,
    ]);

    expect($parentComment->replies)->toHaveCount(1);
    expect($parentComment->replies->first()->id)->toBe($reply->id);
    expect($reply->parent->id)->toBe($parentComment->id);
});
```
