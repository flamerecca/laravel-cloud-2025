# 公告系統 (AnnounceDemo) 文件

## 相關文件
- [介面設計文件](../view/AnnouncementView.md)

## 簡介
本文件介紹了公告系統的資料結構與模型設計。此功能用於在系統中顯示重要的公告訊息。

## 資料庫結構 (Database Schema)

### `announcements` 資料表

| 欄位名稱         | 型別          | 說明                                  |
|--------------|-------------|-------------------------------------|
| `id`         | BigInt (PK) | 自動增量 ID                             |
| `user_id`    | String      | 用戶 ID                               |
| `title`      | String      | 公告標題                                |
| `content`    | Text        | 公告內容                                |
| `status`     | TinyInteger | 公告狀態 (0: unpublished, 1: published) |
| `is_pinned`  | Boolean     | 是否置頂，預設為 `true`                     |
| `created_at` | Timestamp   | 建立時間                                |
| `updated_at` | Timestamp   | 更新時間                                |
| `deleted_at` | Timestamp   | 軟刪除時間 (Soft Delete)                 |

## 模型說明 (Model)

### `App\Models\Announcement`

模型使用了以下特性：
- `HasFactory`: 支援工廠模式進行資料填充。
- `SoftDeletes`: 支援軟刪除功能。

#### 欄位轉換 (Casts)
- `published_at`: `datetime`
- `is_active`: `boolean`

## 快速開始

### 1. 執行遷移
```bash
php artisan migrate
```

### 2. 使用工廠產生測試資料
```php
\App\Models\Announcement::factory()->count(10)->create();
```

### 3. 基本查詢範例
```php
// 取得所有已發佈且啟用的公告
$announcements = Announcement::where('is_active', true)
    ->where('published_at', '<=', now())
    ->orderBy('published_at', 'desc')
    ->get();
```
