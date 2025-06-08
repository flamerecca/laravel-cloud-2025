## 羅馬數字轉阿拉伯數字 API 規格文件 1.0.0

### 概述

將輸入的羅馬數字轉成阿拉伯數字

初期只需要處理 1~10 即可

| 羅馬數字 | 阿拉伯數字 |
|------|-------|
| I    | 1     |
| II   | 2     |
| III  | 3     |
| IV   | 4     |
| V    | 5     |
| VI   | 6     |
| VII  | 7     |
| VIII | 8     |
| IX   | 9     |
| X    | 10    |

---

### Base URL

```
GET /api/roman-convert-to-integer
```

### 📥 Request

### 請求參數（Query Parameters）

| 參數名稱    | 類型     | 必填 | 描述           |
|---------|--------|----|--------------|
| `roman` | string | 是  | 要轉換的正整數（I~X） |

### 規格限制

- 支援羅馬數字（I 到 X）
- 若輸入非羅馬數字或超出範圍，應回傳錯誤訊息

---

### 範例

#### 請求

```http
GET /api/roman-convert-to-integer?roman=IX
```

#### 回應

成功回應（HTTP 200）：

```json
{
    "integer": 9
}
```

錯誤回應（HTTP 422 Unprocessable Entity）：

```json
{
    "error": "Missing or invalid 'roman' parameter."
}
```
