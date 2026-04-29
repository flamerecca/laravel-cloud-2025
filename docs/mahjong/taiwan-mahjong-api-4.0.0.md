# 台式麻將胡牌判斷與台數計算 API 4.0.0 規格書

本文件定義了用於判斷台式麻將（16 張）是否胡牌，並計算胡牌台數的 API 規格，包含場風、門風、花牌、以及 4.0.0 新增的海底狀況計算規則。

## 1. 概述

台式麻將 4.0.0 版本 API 在 3.0.0 的基礎上，新增了「海底狀況」的邏輯判斷。針對摸到最後一張牌（海底撈月）或最後一張打出的牌（河底撈魚）進行台數加計。

## 2. API 端點

### 判斷胡牌與計算台數 (v4)

*   **URL:** `/api/mahjong/check-winning-v4`
*   **Method:** `POST`
*   **Content-Type:** `application/json`

## 3. 請求參數

### JSON Body

| 參數名             | 類型              | 必填 | 說明                                                                    |
|:----------------|:----------------|:---|:----------------------------------------------------------------------|
| `hand`          | `array<string>` | 是  | 包含 17 張牌的陣列（含胡的那張牌）。                                                  |
| `wind_of_round` | `string`        | 是  | 當前場風（圈風），可選值：`east`, `south`, `west`, `north`                         |
| `wind_of_seat`  | `string`        | 是  | 當前門風（位置），可選值：`east`, `south`, `west`, `north`                         |
| `flowers`       | `array<string>` | 否  | 持有的花牌陣列。                                                              |
| `is_dealer`     | `boolean`       | 否  | 是否為莊家，預設為 `false`。                                                    |
| `is_self_drawn` | `boolean`       | 否  | 是否為自摸，預設為 `false`。                                                    |
| `underwater`    | `string`        | 否  | 海底狀況，可選值：`none` (無), `sea_bed` (海底撈月), `river_bed` (河底撈魚)。預設為 `none`。 |

### 牌的表示法 (Tile Notation)

*   **萬子:** `1w`~`9w`, **筒子:** `1t`~`9t`, **條子:** `1s`~`9s`
*   **字牌:** `east`, `south`, `west`, `north`, `red`, `green`, `white`
*   **花牌:** `f1` (春), `f2` (夏), `f3` (秋), `f4` (冬), `f5` (梅), `f6` (蘭), `f7` (竹), `f8` (菊)

## 4. 台數計算規則 (新增/更新)

除了 3.0.0 版本的場風、門風、花牌、莊家規則外，4.0.0 版本新增以下規則：

### 海底狀況 (Underwater Rules)

*   **海底撈月 (1台):** 當 `underwater` 為 `sea_bed` 且 `is_self_drawn` 為 `true` 時觸發。指摸進最後一張海底牌而胡牌。
*   **河底撈魚 (1台):** 當 `underwater` 為 `river_bed` 且 `is_self_drawn` 為 `false` 時觸發。指胡了別人打出的最後一張海底牌。

> **注意:** 
> 1. 若 `underwater` 為 `sea_bed` 但 `is_self_drawn` 為 `false`，則不計「海底撈月」，可能視為無效參數或邏輯衝突（實作時應以自摸為準）。
> 2. 海底撈月通常與「自摸」台數累加。

## 5. 回應格式

### 成功回應 (胡牌)

*   **Status Code:** `200 OK`

```json
{
  "is_winning": true,
  "message": "恭喜胡牌！",
  "total_tai": 11,
  "tai_details": [
    {"name": "底", "tai": 1},
    {"name": "自摸", "tai": 1},
    {"name": "海底撈月", "tai": 1},
    {"name": "場風(東)", "tai": 1},
    {"name": "門風(東)", "tai": 1},
    {"name": "正花", "tai": 1},
    {"name": "莊家", "tai": 1},
    {"name": "碰碰胡", "tai": 4}
  ],
  "combination": {
    "mianzi": [
      ["east", "east", "east"],
      ["red", "red", "red"],
      ["green", "green", "green"],
      ["1w", "1w", "1w"],
      ["2t", "2t", "2t"]
    ],
    "pair": ["white", "white"]
  }
}
```

## 6. 範例案例 (v4 特色)

### 案例 1：海底撈月 (Sea Bed)
*   **說明:** 莊家在東風場東位，摸到最後一張海底牌胡牌（自摸）。
*   **請求:**
```json
{
  "hand": ["1w", "1w", "1w", "2w", "3w", "4w", "5s", "5s", "5s", "east", "east", "east", "red", "red", "red", "white", "white"],
  "wind_of_round": "east",
  "wind_of_seat": "east",
  "is_dealer": true,
  "is_self_drawn": true,
  "underwater": "sea_bed"
}
```
*   **台數計算:** 底(1) + 自摸(1) + 海底撈月(1) + 場風東(1) + 門風東(1) + 莊家(1) = 6台。

### 案例 2：河底撈魚 (River Bed)
*   **說明:** 非莊家在南風場北位，胡了別人打出的最後一張牌。
*   **請求:**
```json
{
  "hand": ["1t", "2t", "3t", "4t", "5t", "6t", "7t", "8t", "9t", "south", "south", "south", "west", "west", "west", "white", "white"],
  "wind_of_round": "south",
  "wind_of_seat": "north",
  "is_self_drawn": false,
  "underwater": "river_bed"
}
```
*   **台數計算:** 底(1) + 河底撈魚(1) + 平胡(1) + 場風南(1) = 4台。

### 案例 3：海底狀況與邏輯衝突處理
*   **說明:** 若標記為 `river_bed` 卻標記為 `is_self_drawn: true`，API 應優先根據 `is_self_drawn` 判斷。在此案例中，因 `river_bed` 邏輯上不應是自摸，API 應只計算自摸台。
*   **請求:**
```json
{
  "hand": ["1s", "2s", "3s", "4s", "5s", "6s", "7s", "8s", "9s", "1w", "1w", "1w", "2w", "2w", "2w", "3w", "3w"],
  "wind_of_round": "west",
  "wind_of_seat": "west",
  "is_self_drawn": true,
  "underwater": "river_bed"
}
```
*   **回應台數詳情 (部分):**
    *   `{"name": "底", "tai": 1}`
    *   `{"name": "自摸", "tai": 1}`
    *   `{"name": "清一色", "tai": 8}`
    *   `{"name": "門風(西)", "tai": 1}`
    *   *(無河底撈魚台)*
