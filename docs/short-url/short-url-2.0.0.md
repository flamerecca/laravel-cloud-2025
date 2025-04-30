## Short URL API 規格書 2.0.0

### 概述

`Short URL API 2.0.0` 提供比起 [Short URL API 1.0.0](short-url-1.0.0.md) 更進階的服務，可在建立 API 時加上網址擁有者（domain
owners）的資訊。

網址擁有者的資訊需獨立建立自己的資料表。

每個短網址 (`short-url`) 會「屬於」某一個 `domain-owner`
用 **`domain_owner_id`** 這個欄位來做關聯

#### 網址擁有者資源名稱

`domain-owner`

#### 網址擁有者資料欄位範例

| 欄位         | 類型       | 說明          |
|------------|----------|-------------|
| id         | UUID     | 主鍵          |
| name       | String   | 擁有者名稱       |
| email      | String   | 擁有者聯絡 Email |
| company    | String   | 公司名稱（選填）    |
| created_at | DateTime | 建立時間        |
| updated_at | DateTime | 更新時間        |

### Base URL

```
/api/creaters
```

#### 建立 domain-owners

##### Endpoint

```
POST /api/domain-owners
```

##### Request Headers

| 名稱           | 值                |
|--------------|------------------|
| Content-Type | application/json |

##### Request Body

```json
{
    "name": "Alice Chen",
    "email": "alice@example.com",
    "company": "SuperDomain Inc."
}
```

##### Response（201 Created）

```json
{
    "id": "1",
    "name": "Alice Chen",
    "email": "alice@example.com",
    "company": "SuperDomain Inc.",
    "created_at": "2025-04-28T12:00:00Z",
    "updated_at": "2025-04-28T12:00:00Z"
}
```

#### 查詢單一 domain-owner

##### Endpoint

```
GET /api/domain-owners/{id}
```

##### Response 200 OK

```json
{
    "id": "1",
    "name": "Alice Chen",
    "email": "alice@example.com",
    "company": "SuperDomain Inc.",
    "created_at": "2025-04-28T12:00:00Z",
    "updated_at": "2025-04-28T12:00:00Z"
}
```

#### 列出所有 domain-owners

##### Endpoint

```
GET /api/domain-owners/
```

##### Response 200 OK

```json
{
    "data": [
        {
            "id": "1",
            "name": "Alice Chen",
            "email": "alice@example.com",
            "company": "SuperDomain Inc.",
            "created_at": "2025-04-28T12:00:00Z",
            "updated_at": "2025-04-28T12:00:00Z"
        },
        {
            "id": "2",
            "name": "Bob Chen",
            "email": "bob@example.com",
            "company": "SuperDomain Inc.",
            "created_at": "2025-04-28T12:00:00Z",
            "updated_at": "2025-04-28T12:00:00Z"
        }
    ]
}
```

#### 更新 domain-owner

##### Endpoint

```
PATCH /api/domain-owners/{id}
```

##### Request Body

```json
{
    "email": "newemail@example.com",
    "company": "New Company Name"
}
```

##### Response 200 OK

```json
{
    "id": "1",
    "name": "Alice Chen",
    "email": "newemail@example.com",
    "company": "New Company Name",
    "created_at": "2025-04-28T12:00:00Z",
    "updated_at": "2025-04-29T09:00:00Z"
}
```

#### 刪除 domain-owner

##### Request Body

```
DELETE /api/domain-owners/{id}
```

##### Response 204 No Content

刪除成功不回傳資料

### 短網址串接 domain-owner API

#### 更新短網址綁定的 domain-owner

##### Request URL

```
PATCH /short-urls/{id}/domain-owner
```

##### Request Body

```json
{
    "domain_owner_id": "1"
}
```

##### Response 200 OK

```json
{
    "id": "23",
    "original_url": "https://example.com",
    "short_code": "exmp",
    "domain_owner": {
        "id": "1",
        "name": "Alice Chen",
        "email": "alice@example.com",
        "company": "SuperDomain Inc."
    }
}
```

#### 查詢短網址的 domain-owner 資訊

```
GET /short-urls/{id}/domain-owner
```

##### Response 200 OK

```json
{
    "id": "1",
    "name": "Alice Chen",
    "email": "alice@example.com",
    "company": "SuperDomain Inc."
}
```
