## Three Divisors API 規格文件 1.0.0

### 概述

`Three Divisors API` 提供判斷數字的服務。本 API 接收一個整數 `n`，若 n 恰好有三個正因數，則回傳 `true`；否則回傳 `false`。

因數的定義：若存在一個整數 k，使得 n = k × m，則整數 m 是 n 的因數。

### Base URL

```
GET /api/three-divisors
```

### Request Headers

| 名稱           | 值                |
|--------------|------------------|
| Content-Type | application/json |

### 請求參數（Query Parameters）

| 名稱 | 類型      | 是否必填 | 說明     |
|----|---------|------|--------|
| n  | integer | 是    | 要判斷的數字 |

### 範例請求

```http
GET /api/three-divisors?n=4
```

### 範例回應

```json
{
    "n": 4,
    "isThree": true
}
```
### 失敗回應

- **範例：n 不是整數**

```json
{
    "error": "'n' must be an integer."
}
```

(p.s. 到這一階段，你已經完成了 Leetcode 1952. Three Divisors https://leetcode.com/problems/three-divisors/)


### 附加思考

目前寫法是效率最好的寫法嗎？最快應該可以到 big-O 的哪個等級？
