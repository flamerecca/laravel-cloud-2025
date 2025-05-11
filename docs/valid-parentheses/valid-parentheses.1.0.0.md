## Valid Parentheses API 規格文件 1.0.0

### 概述

此 API 可驗證一段字串是否為有效的括號組合。
括號只包含 `( ) { } [ ]` 六種符號。
有效的括號組合定義：每一個開括號都有正確順序與正確型態的閉括號對應。

### Base URL

```
POST /api/valid-parentheses
```

### 請求 Header

* **Content-Type**: `application/json`

#### Request Body

| 欄位名稱  | 類型     | 必要 | 描述       | 範例         |
|-------|--------|----|----------|------------|
| input | string | 是  | 欲驗證的括號字串 | `"{[()]}"` |

### Request Body 範例

```json
{
    "input": "{[()]}"
}
```

### 回應

* **Content-Type**: `application/json`

#### Response Body

| 欄位名稱  | 類型      | 描述               | 範例         |
|-------|---------|------------------|------------|
| input | string  | 處理的括號字串          | `"{[()]}"` |
| valid | boolean | 若為 true 表示括號組合有效 | `true`     |

```json
{
    "input": "{[()]}",
    "valid": true
}
```

### 回應狀態碼

| 狀態碼                        | 說明                            |
|----------------------------|-------------------------------|
| `200 OK`                   | 成功驗證並回傳結果                     |
| `422 UNPROCESSABLE ENTITY` | 請求格式錯誤，例如缺少 `input` 欄位或資料型別錯誤 |

#### 422 UNPROCESSABLE ENTITY 範例

```json
{
    "message": "The 'input' field must only contain (), {}, [].",
    "errors": {
        "input": [
            "The 'input' field must only contain (), {}, []."
        ]
    }
}
```

---

### 錯誤處理

| 狀態碼 | 原因             | 解決方式            |
|-----|----------------|-----------------|
| 400 | 缺少 input 或格式錯誤 | 檢查並修正請求 JSON 格式 |

---

### 限制

* 最大 input 長度：1000 字元
* 僅接受 `()`, `{}`, `[]` 三種括號，不支援其他符號

(p.s. 到這一階段，你已經完成了 20. Valid Parentheses https://leetcode.com/problems/valid-parentheses/)
