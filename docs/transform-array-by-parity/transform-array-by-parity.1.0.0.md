## Transform Array by Parity API 規格文件 1.0.0

### 概述

`Transform Array by Parity API` 提供陣列處理的服務。本 API 接收一個整數陣列 `nums` `[x1, x2,..., xn, y1, y2,..., yn]`。

請依照以下順序對 nums 進行轉換：

- 將每個偶數替換為 0。
- 將每個奇數替換為 1。
- 將修改後的陣列按遞增順序排序。

回傳執行這些操作後得到的陣列。

### Base URL

```
GET /api/transform-array-by-parity
```

### 請求參數（Query Parameters）

| 名稱     | 類型        | 是否必填 | 說明   |
|--------|-----------|------|------|
| nums[] | integer[] | 是    | 一組數字 |

### 📥 範例請求

```http
GET /api/transform-array-by-parity?nums[]=4&nums[]=3&nums[]=2&nums[]=1
```

### 📤 範例回應

```json
{
  "results": [0, 0, 1, 1]
}
```

(p.s. 到這一階段，你已經完成了 3467. Transform Array by Parity https://leetcode.com/problems/transform-array-by-parity/)
