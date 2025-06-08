## Short URL API 規格書 3.0.0

### 概述

`Short URL API 3.0.0` 提供比起 [Short URL API 2.0.0](short-url-2.0.0.md) 更進階的服務，可在建立 API
時加上網址標籤（domain_tag）的資訊。

網址標籤的資訊需獨立建立自己的資料表。

每個短網址 (`short-url`) 和網址標籤屬於多對多的關聯。

可以透過標籤取出關聯的所有短網址，也可以透過短網址取出相關的所有標籤

#### 網址標籤名稱

`domain-tag`

#### 網址標籤資料欄位範例

| 欄位         | 類型       | 說明     |
|------------|----------|--------|
| id         | UUID     | 主鍵     |
| name       | String   | 網址標籤名稱 |
| created_at | DateTime | 建立時間   |
| updated_at | DateTime | 更新時間   |

#### 建立短網址時可加上標籤

##### Endpoint

```
POST /api/short-urls
```

##### Request Headers

| 名稱           | 值                |
|--------------|------------------|
| Content-Type | application/json |

##### Request Body

| 欄位           | 型別              | 說明            |
|--------------|-----------------|---------------|
| original_url | string          | 原始網址 (必填)     |
| tags         | array of string | 要加的標籤名稱們 (可選) |

```json
{
    "original_url": "https://www.example.com/some/very/long/url",
    "domain_tags": [
        "example",
        "https"
    ]
}
```

##### Response 201 Created

```json
{
    "id": "1",
    "slug": "abcdqwerzx",
    "original_url": "https://www.example.com/some/very/long/url",
    "domain_tags": [
        "example",
        "https"
    ],
    "created_at": "2025-04-26 12:34:56"
}
```

##### API 需要注意的地方

- tags 傳入如果有新標籤名稱，後端會自動 firstOrCreate。
- tags 可以不傳，不傳就代表沒標籤。

#### 更新短網址的標籤

##### Endpoint

```
PUT /api/short-urls/{id}/tags
```

### URL Params

| 參數 | 說明           |
|----|--------------|
| id | 要更新標籤的短網址 ID |

---

### Request Body

```json
{
    "domain_tags": [
        "promotion",
        "social-media"
    ]
}
```

| 欄位   | 型別              | 說明       |
|------|-----------------|----------|
| tags | array of string | 要綁的新標籤列表 |

---

### Response 200 OK

回傳更新後的 `short-urls`

```json
{
    "id": "1",
    "slug": "abcdqwerax",
    "original_url": "https://www.example.com/some/very/long/url",
    "domain_tags": [
        "promotion",
        "social-media"
    ],
    "created_at": "2025-04-26 12:34:56"
}
```
##### API 需要注意的地方

- **更新標籤是「覆蓋式」**：傳什麼，舊的全部換掉。

#### 取得短網址資訊時，需包含標籤

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
    "created_at": "2025-04-26 12:34:56"
}
```

##### API 需要注意的地方

- 只改變取得短網址資訊 API，不改變取得所有短網址的 API
