## API 標題 API 規格文件 1.0.0

### 概述

在這裡簡要描述這個 API 的功能與目的。

---

### Base URL

```
GET /api/resource-name
```

### 📥 Request

### 請求參數（Query Parameters）

| 參數名稱     | 類型      | 必填 | 描述          |
|----------|---------|----|-------------|
| `param1` | string  | 是  | 參數一的說明。     |
| `param2` | integer | 否  | 參數二的說明（可選）。 |

### 規格限制

- 說明此 API 的使用限制或規則。
- 例如：`param2` 必須大於 0。

---

### 範例

#### 請求

```http
GET /api/resource-name?param1=value1&param2=123
```

#### 回應

成功回應（HTTP 200 OK）：

```json
{
    "key": "value"
}
```

錯誤回應（HTTP 422 Unprocessable Entity）：

```json
{
    "error": "錯誤訊息說明。"
}
```
