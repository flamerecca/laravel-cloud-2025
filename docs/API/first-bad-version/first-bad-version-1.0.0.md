## 判斷壞版本版本號 規格文件 1.0.0

### 概述

判斷第一個壞版本（Bad Version Finder）

透過查詢  `/bad-version` 外部 API，找出 1 ~ 2^20 範圍內第一個壞版本（bad version），以減少查詢次數為原則實作。

### Base URL

```
GET /api/first-bad-version
```
### 回傳格式（Response Format）

#### 成功回應 (200 OK)

```json
{
    "version": 21
}
```

### 設計邏輯

採用 Binary Search 進行搜尋


(p.s. 到這一階段，你已經完成了 Leetcode 278. First Bad Version https://leetcode.com/problems/first-bad-version/)
