## Short URL API 規格書 4.0.0

### 概述

`Short URL API 4.0.0` 提供比起 [Short URL API 3.0.0](short-url-3.0.0.md) 更進階的服務。

短網址加上啟用設置，可以設置啟用與否，以及設置啟用時間與停用時間。

### 資源名稱

`ShortUrl`

### Base URL

```
/api/short-urls
```

### 短網址資料結構調整

| 參數名稱           | 類型      | 描述                     |
|----------------|---------|------------------------|
| `id`           | integer | 短網址資料 id               |
| `original_url` | text    | 原始網址                   |
| `slug`         | string  | 短網址結尾                  |
| `is_active`    | boolean | 是否啟用 （可選，預設為啟用）        |
| `started_at`   | date    | 開始時間（可選，預設為資料建立日期）     |
| `ended_at`     | date    | 到期時間（可選，預設為資料建立日期加上一年） |

#### 取得短網址資訊時，需包含啟用設置與啟用停用時間資訊

##### Endpoint

```
GET /short-urls/{id}
```

##### Response (200 OK)

```json
{
    "id": "1",
    "slug": "abcdqwerax",
    "original_url": "https://www.example.com/some/very/long/url",
    "domain_tags": [
        "promotion",
        "social-media"
    ],
    "is_active": true,
    "started_at": "2025-04-26",
    "ended_at": "2030-04-26",
    "created_at": "2025-04-26 12:34:56"
}
```
