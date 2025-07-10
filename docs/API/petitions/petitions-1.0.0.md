## 連署頁面 API 規格文件 1.0.0

### 概述

提供一個端點讓使用者可以參與連署活動，提交個人資訊以表示支持。

---

### Base URL

```
POST /api/signatures
```

### 📥 Request

### 請求主體（Request Body）

| 參數名稱      | 類型     | 必填 | 描述        |
|-----------|--------|----|-----------|
| `name`    | string | 是  | 連署人姓名     |
| `email`   | string | 是  | 連署人電子郵件   |
| `phone`   | string | 否  | 連署人聯絡電話   |
| `comment` | string | 否  | 給活動發起者的留言 |

### 規格限制

- `name` 與 `email` 為必填欄位。
- `email` 必須是有效的電子郵件格式。
- 同一個 `email` 不可重複連署。

---

### 範例

#### 請求

```http
POST /api/signatures
Content-Type: application/json

{
    "name": "陳大文",
    "email": "david.chen@example.com",
    "comment": "支持這個活動！"
}
```

#### 回應

成功回應（HTTP 201 Created）：

```json
{
    "id": 1,
    "name": "陳大文",
    "email": "david.chen@example.com",
    "phone": null,
    "comment": "支持這個活動！",
    "created_at": "2025-07-10T10:00:00.000000Z"
}
```

錯誤回應 - 驗證失敗（HTTP 422 Unprocessable Entity）：

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": [
            "The email field is required."
        ]
    }
}
```

錯誤回應 - 重複連署（HTTP 422 Unprocessable Entity）：

```json
{
    "error": "This email has already been used to sign."
}
```
