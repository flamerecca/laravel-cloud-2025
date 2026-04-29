# 台式麻將胡牌判斷與台數計算 API 3.0.0 規格書

本文件定義了用於判斷台式麻將（16 張）是否胡牌，並計算胡牌台數的 API 規格，包含場風、門風、花牌的計算規則。

## 1. 概述

台式麻將 3.0.0 版本 API 在 2.0.0 的基礎上，完善了「台數計算」功能，正式納入場風、門風、花牌的邏輯判斷。

## 2. API 端點

### 判斷胡牌與計算台數 (v3)

*   **URL:** `/api/mahjong/check-winning-v3`
*   **Method:** `POST`
*   **Content-Type:** `application/json`

## 3. 請求參數

### JSON Body

| 參數名             | 類型              | 必填 | 說明                                            |
|:----------------|:----------------|:---|:----------------------------------------------|
| `hand`          | `array<string>` | 是  | 包含 17 張牌的陣列（含胡的那張牌）。                          |
| `wind_of_round` | `string`        | 是  | 當前場風（圈風），可選值：`east`, `south`, `west`, `north` |
| `wind_of_seat`  | `string`        | 是  | 當前門風（位置），可選值：`east`, `south`, `west`, `north` |
| `flowers`       | `array<string>` | 否  | 持有的花牌陣列。                                      |
| `is_dealer`     | `boolean`       | 否  | 是否為莊家，預設為 `false`。                            |
| `is_self_drawn` | `boolean`       | 否  | 是否為自摸，預設為 `false`。                            |

### 牌的表示法 (Tile Notation)

*   **萬子:** `1w`~`9w`, **筒子:** `1t`~`9t`, **條子:** `1s`~`9s`
*   **字牌:** `east`, `south`, `west`, `north`, `red`, `green`, `white`
*   **花牌:** `f1` (春), `f2` (夏), `f3` (秋), `f4` (冬), `f5` (梅), `f6` (蘭), `f7` (竹), `f8` (菊)

## 4. 台數計算規則 (新增/更新)

除了基本台數（如底、碰碰胡、平胡等），3.0.0 版本明確以下規則：

### 場風與門風 (Wind Rules)
*   **場風台 (1台):** 刻子與當前 `wind_of_round` 相同。
*   **門風台 (1台):** 刻子與當前 `wind_of_seat` 相同。
*   **雙風台 (2台):** 刻子同時為場風與門風（例如：東風圈、東家）。

### 花牌 (Flower Rules)
*   **正花 (1台):** 獲得與自己門風對應的花牌。
    *   東家：`f1` (春), `f5` (梅)
    *   南家：`f2` (夏), `f6` (蘭)
    *   西家：`f3` (秋), `f7` (竹)
    *   北家：`f4` (冬), `f8` (菊)
*   **花槓 (2台):** 集齊春夏秋冬 (`f1`-`f4`) 或 梅蘭竹菊 (`f5`-`f8`)。

### 莊家相關 (Dealer Rules)
*   **莊家 (1台):** 胡牌或被胡時計算。
*   **連莊 (2n台):** 根據連莊次數累加。

## 5. 回應格式

### 成功回應 (胡牌)

*   **Status Code:** `200 OK`

```json
{
  "is_winning": true,
  "message": "恭喜胡牌！",
  "total_tai": 10,
  "tai_details": [
    {"name": "底", "tai": 1},
    {"name": "場風(東)", "tai": 1},
    {"name": "門風(東)", "tai": 1},
    {"name": "正花", "tai": 1},
    {"name": "莊家", "tai": 1},
    {"name": "碰碰胡", "tai": 4},
    {"name": "門清", "tai": 1}
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

## 6. 範例案例 (v3 特色)

### 案例 1：東風場 東家 正花胡牌
*   **請求:**
```json
{
  "hand": ["east", "east", "east", "1w", "2w", "3w", "4s", "5s", "6s", "7t", "8t", "9t", "red", "red", "red", "white", "white"],
  "wind_of_round": "east",
  "wind_of_seat": "east",
  "flowers": ["f1", "f6"],
  "is_dealer": true
}
```
*   **台數計算:** 底(1) + 雙風(2) + 三元牌中(1) + 正花f1(1) + 莊家(1) = 6台。

### 案例 2：西風場 北家 花槓胡牌
*   **請求:**
```json
{
  "hand": ["1w", "1w", "1w", "2w", "3w", "4w", "5s", "5s", "5s", "6s", "7s", "8s", "west", "west", "west", "white", "white"],
  "wind_of_round": "west",
  "wind_of_seat": "north",
  "flowers": ["f5", "f6", "f7", "f8"],
  "is_self_drawn": true
}
```
*   **台數計算:** 底(1) + 場風西(1) + 花槓(2) + 自摸(1) + 門清(1) = 6台。

### 案例 3：失敗案例 (花牌不計入胡牌張數)
*   **說明:** 手牌必須湊齊 17 張，花牌是額外計算的。
*   **請求:**
```json
{
  "hand": ["1w", "2w", "3w", "4w", "5w", "6w", "7w", "8w", "9w", "1t", "2t", "3t", "4t", "5t", "6t", "white"],
  "wind_of_round": "east",
  "wind_of_seat": "east",
  "flowers": ["f1"]
}
```
*   **回應:**
```json
{
  "is_winning": false,
  "message": "尚未胡牌。",
  "reason": "牌數不正確，扣除花牌後手牌應為 17 張。"
}
```

### 案例 4：大四喜 (雙風台 + 碰碰胡)
*   **情境:** 東風場東家，手持有東南西北刻子，外加萬子刻子與對子。
*   **請求:**
```json
{
  "hand": ["east", "east", "east", "south", "south", "south", "west", "west", "west", "north", "north", "north", "1w", "1w", "1w", "2w", "2w"],
  "wind_of_round": "east",
  "wind_of_seat": "east",
  "flowers": ["f1", "f5"],
  "is_dealer": true
}
```
*   **台數計算:** 底(1) + 大四喜(16) + 雙風東(2) + 碰碰胡(4) + 正花(2) + 莊家(1) = 26台。

### 案例 5：八仙過海 (特殊花牌胡牌)
*   **說明:** 收集齊全部 8 張花牌（f1-f8），在台式麻將中通常直接計為胡牌或加計極高台數。
*   **請求:**
```json
{
  "hand": ["1w", "2w", "3w", "4w", "5w", "6w", "7w", "8w", "9w", "1t", "2t", "3t", "4t", "5t", "6t", "white", "white"],
  "wind_of_round": "south",
  "wind_of_seat": "west",
  "flowers": ["f1", "f2", "f3", "f4", "f5", "f6", "f7", "f8"],
  "is_self_drawn": true
}
```
*   **回應範例:**
```json
{
  "is_winning": true,
  "message": "恭喜胡牌！(八仙過海)",
  "total_tai": 16,
  "tai_details": [
    {"name": "底", "tai": 1},
    {"name": "八仙過海", "tai": 8},
    {"name": "花槓(春夏秋冬)", "tai": 2},
    {"name": "花槓(梅蘭竹菊)", "tai": 2},
    {"name": "正花(f3, f7)", "tai": 2},
    {"name": "自摸", "tai": 1}
  ],
  "combination": {
    "mianzi": [["1w", "2w", "3w"], ["4w", "5w", "6w"], ["7w", "8w", "9w"], ["1t", "2t", "3t"], ["4t", "5t", "6t"]],
    "pair": ["white", "white"]
  }
}
```

### 案例 6：清一色 碰碰胡 帶莊家
*   **情境:** 莊家，全手牌皆為萬子且為刻子。
*   **請求:**
```json
{
  "hand": ["1w", "1w", "1w", "2w", "2w", "2w", "4w", "4w", "4w", "5w", "5w", "5w", "8w", "8w", "8w", "9w", "9w"],
  "wind_of_round": "north",
  "wind_of_seat": "south",
  "is_dealer": true
}
```
*   **台數計算:** 底(1) + 清一色(8) + 碰碰胡(4) + 門清(1) + 莊家(1) = 15台。
