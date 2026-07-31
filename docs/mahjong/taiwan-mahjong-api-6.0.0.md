# 台式麻將胡牌判斷與進階分析 API 6.0.0 規格書

本文件定義台式麻將（16 張）「胡牌判斷 / 台數分析 / 打牌建議」API 的 6.0.0 規格。此版本重點為新增 `has_flowers` 參數，讓呼叫端可依「本場規則有花 / 無花」切換驗證與計算邏輯。

## 1. 概述

6.0.0 延續 5.0.0 的所有能力，並加入規則切換：

1. `has_flowers = true`：代表本場為「有花」規則。
   * 請求可帶入 `flowers`。
   * 胡牌台數分析納入花牌相關台數。
2. `has_flowers = false`：代表本場為「無花」規則。
   * `flowers` 必須為空陣列。
   * 所有花牌相關台數與描述皆不計入。

> 相容性：若舊版客戶端未傳 `has_flowers`，伺服器應以 `false` 為預設值，確保可向後相容。

## 2. API 端點

### 2.1 判斷胡牌與計算台數 (v6)
*   **URL:** `/api/mahjong/v6/check-winning`
*   **Method:** `POST`
*   **Content-Type:** `application/json`

### 2.2 打牌建議分析 (v6)
*   **URL:** `/api/mahjong/v6/analyze-discard`
*   **Method:** `POST`
*   **Content-Type:** `application/json`

## 3. 請求參數

### 3.1 通用 JSON Body（兩支 API 皆適用）

| 參數名                 | 類型              | 必填 | 預設值     | 說明                                                                |
|:--------------------|:----------------|:---|:--------|:------------------------------------------------------------------|
| `hand`              | `array<string>` | 是  | -       | 手牌陣列。`check-winning` 須為 17 張；`analyze-discard` 須為 17 張（模擬打一張後分析）。 |
| `wind_of_round`     | `string`        | 是  | -       | 場風（`east` / `south` / `west` / `north`）。                          |
| `wind_of_seat`      | `string`        | 是  | -       | 門風（`east` / `south` / `west` / `north`）。                          |
| `has_flowers`       | `boolean`       | 否  | `false` | 本場是否為有花規則。`true` 表示有花，`false` 表示無花。                               |
| `flowers`           | `array<string>` | 否  | `[]`    | 玩家持有花牌。僅在 `has_flowers=true` 時可傳入。                                |
| `discards_on_table` | `array<string>` | 否  | `[]`    | 檯面已打出的牌（含所有人），供精確計算剩餘張數（主要用於 `analyze-discard`）。                  |

### 3.2 參數關聯驗證規則

1. `has_flowers = true`：
   * `flowers` 為空陣列或提供花牌陣列。
2. `has_flowers = false`：
   * `flowers` 必須為空陣列。
   * 若傳入非空 `flowers`，回傳 `422 Unprocessable Entity`。

## 4. 回應格式

### 4.1 `POST /api/mahjong/v6/check-winning` 成功回應

*   **Status Code:** `200 OK`

```json
{
  "is_winning": true,
  "total_tai": 5,
  "tai_breakdown": [
    {"name": "莊家", "tai": 1},
    {"name": "門清", "tai": 1},
    {"name": "自摸", "tai": 1},
    {"name": "花牌", "tai": 2}
  ],
  "rule": {
    "has_flowers": true
  },
  "message": "恭喜胡牌！"
}
```

> 當 `has_flowers=false` 時，`tai_breakdown` 不得出現任何花牌相關項目。

### 4.2 `POST /api/mahjong/v6/analyze-discard` 成功回應

*   **Status Code:** `200 OK`

```json
{
  "rule": {
    "has_flowers": false
  },
  "suggestions": [
    {
      "discard": "1w",
      "is_ting": true,
      "ting_tiles": [
        {"tile": "2w", "remaining": 3},
        {"tile": "5w", "remaining": 4}
      ],
      "total_remaining": 7,
      "estimated_tai_if_win": 3,
      "description": "打出 1w 後聽 2w, 5w，共 7 張牌。"
    }
  ],
  "best_discard": "1w"
}
```

## 5. 錯誤回應

### 5.1 規則衝突（無花規則卻傳入花牌）

*   **Status Code:** `422 Unprocessable Entity`

```json
{
  "error": "參數驗證失敗",
  "details": {
    "flowers": [
      "當 has_flowers 為 false 時，不可傳入 flowers。"
    ]
  }
}
```

### 5.2 牌數錯誤

*   **Status Code:** `422 Unprocessable Entity`

```json
{
  "error": "牌數不正確，台式麻將胡牌需為 17 張牌。"
}
```

## 6. 核心邏輯判斷標準

1. **胡牌結構判斷：** 以 17 張牌檢查是否符合「五面子 + 一對子」。
2. **聽牌分析：**
   * `analyze-discard` 遍歷 17 張手牌，模擬打出每一張，檢查 16 張牌可由哪些牌補成胡牌。
3. **剩餘張數計算：**
   * `remaining = 4 - 手牌中該牌數量 - 檯面中該牌數量`。
4. **最佳打法：** 優先 `total_remaining` 較高者；若同分，可再以 `estimated_tai_if_win`、牌效率等次序排序。
5. **花牌規則切換：**
   * `has_flowers=true`：可讀取 `flowers` 參與台數計算與描述。
   * `has_flowers=false`：忽略並禁止非空 `flowers`，所有花牌台數視為 0。

## 7. 範例案例

### 案例 1：有花規則（`has_flowers=true`）

```json
{
  "hand": ["1w", "2w", "3w", "4w", "5w", "6w", "7t", "8t", "9t", "2s", "2s", "2s", "east", "east", "east", "red", "red"],
  "wind_of_round": "east",
  "wind_of_seat": "east",
  "has_flowers": true,
  "flowers": ["plum", "orchid"]
}
```

* 預期：可正常計算胡牌與台數，並可能出現花牌台數。

### 案例 2：無花規則（`has_flowers=false`）

```json
{
  "hand": ["1w", "2w", "3w", "4w", "5w", "6w", "7t", "8t", "9t", "2s", "2s", "2s", "east", "east", "east", "red", "red"],
  "wind_of_round": "east",
  "wind_of_seat": "south",
  "has_flowers": false
}
```

* 預期：不使用花牌邏輯，回應中不應出現花牌台數。

### 案例 3：無花規則但傳入 flowers（應報錯）

```json
{
  "hand": ["1w", "2w", "3w", "4w", "5w", "6w", "7t", "8t", "9t", "2s", "2s", "2s", "east", "east", "east", "red", "red"],
  "wind_of_round": "south",
  "wind_of_seat": "west",
  "has_flowers": false,
  "flowers": ["plum"]
}
```

* 預期：`422`，提示 `has_flowers=false` 時不可傳入非空 `flowers`。

## 8. 版本升級說明（5.0.0 → 6.0.0）

1. 端點版本升級：`/api/mahjong/v5/*` → `/api/mahjong/v6/*`。
2. 新增 `has_flowers:boolean`（預設 `false`）。
3. 明確定義 `has_flowers` 與 `flowers` 的關聯驗證規則。
4. 回應新增 `rule.has_flowers`，便於前端回顯本次採用規則。
