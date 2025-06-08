## 最大公因數 API 規格文件 1.0.0

### 概述

輸入兩個正整數，計算並回傳其 最大公因數 (GCD) 。

---

### Base URL

```
GET /api/gcd
```

### 請求參數（Query Parameters）

| 參數名稱 | 類型      | 必填 | 描述        |
|------|---------|----|-----------|
| `a`  | integer | 是  | 處理的第一個正整數 |
| `b`  | integer | 是  | 處理的第二個正整數 |


### 回傳格式（Response Format）

#### 成功回應 (200 OK)

```json
{
    "a": 12,
    "b": 18,
    "gcd": 6
    
}
```

#### 失敗回應（例如參數錯誤）

- **422 Unprocessable Entity**

```json
{
    "error": "Invalid input. Parameters a and b must be positive integers."
}
```

---

### 範例請求

```
GET /api/gcd?a=21&b=14
```

回傳內容

```json
{
    "a": 21,
    "b": 14,
    "gcd": 7
}
```
