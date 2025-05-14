## slow order 第三方 API 存取與驗證 API 1.0.0

### 概述

`slow-order` 提供判斷訂單合法性功能。需透過第三方 API 進行驗證，反應很慢，所以取名 slow order。

會透過第三方 API 進行三項檢查

- 訂單用戶合法
- 將美金換算成台幣
- 確認訂單日期合法

每次請求會花費約三秒取得回傳。

為了提升效率，我們應該並行存取 API。

> 本練習旨在訓練學員使用 Laravel Http::pool 進行 API 並行請求，以提升學員開發高效率 API 客戶端的能力。

### Base URL

```
POST /api/slow-order
```

### Request Headers

| 名稱           | 值                |
|--------------|------------------|
| Content-Type | application/json |

### Request Body 範例

```json
{
    "user": "Alice",
    "usd": 30,
    "date": "2025-05-02"
}
```

#### 欄位說明：

| 欄位     | 類型      | 描述     | 必填 |
|--------|---------|--------|----|
| `user` | string  | 訂單用戶名稱 | ✅  |
| `usd`  | integer | 美金價格   | ✅  |
| `date` | string  | 訂單日期   | ✅  |

### 使用的 API

#### 確認訂單用戶合法

##### base URL

```
GET https://laravelslowapi-main-j3y6l3.laravel.cloud/api/user-check
```

##### Request

###### 請求參數（Query Parameters）

| 參數名稱   | 類型     | 必填 | 描述   |
|--------|--------|----|------|
| `user` | string | 是  | 用戶姓名 |

###### 範例請求

```http
GET https://laravelslowapi-main-j3y6l3.laravel.cloud/api/user-check?user=alice
```

##### 回應

成功回應（HTTP 200）：

```json
{
    "user": "alice",
    "response": "user information has been verified"
}
```

#### 將美金換算成台幣

##### base URL

```
GET https://laravelslowapi-main-j3y6l3.laravel.cloud/api/exchange-rate
```

##### Request

###### 請求參數（Query Parameters）

| 參數名稱  | 類型     | 必填 | 描述     |
|-------|--------|----|--------|
| `usd` | string | 是  | 要轉換的金額 |

###### 範例請求

```http
GET https://laravelslowapi-main-j3y6l3.laravel.cloud/api/exchange-rate?usd=30
```

##### 回應

成功回應（HTTP 200）：

```json
{
    "usd": 30,
    "twd": 900
}
```

#### 確認訂單日期合法

##### base URL

```
GET https://laravelslowapi-main-j3y6l3.laravel.cloud/api/date-check
```

##### Request

###### 請求參數（Query Parameters）

| 參數名稱   | 類型     | 必填 | 描述     |
|--------|--------|----|--------|
| `date` | string | 是  | 要檢查的日期 |

###### 範例請求

```http
GET https://laravelslowapi-main-j3y6l3.laravel.cloud/api/date-check?date=2025-05-02
```

##### 回應

成功回應（HTTP 200）：

```json
{
    "date": "2025-05-02",
    "isChecked": true
}
```

### 回傳

#### 回傳欄位說明：

| 欄位            | 類型      | 描述      |
|---------------|---------|---------|
| `userChecked` | string  | 用戶確認後回應 |
| `twd`         | integer | 台幣價格    |
| `dateChecked` | boolean | 日期檢查後結果 |

#### 回傳範例

```json
{
    "userChecked": "user information has been verified",
    "twd": 300,
    "dateChecked": true
}
```
