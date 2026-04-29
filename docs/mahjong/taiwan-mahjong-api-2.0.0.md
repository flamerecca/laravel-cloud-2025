# 台式麻將胡牌判斷與台數計算 API 2.0.0 規格書

本文件定義了用於判斷台式麻將（16 張）是否胡牌，並計算胡牌台數的 API 規格。

## 1. 概述

台式麻將 2.0.0 版本 API 除了判斷手牌是否符合「五面子 + 一對子」的胡牌規則外，新增了「台數計算」功能。不包含風圈、門風、花牌的計算。

## 2. API 端點

### 判斷胡牌與計算台數

*   **URL:** `/api/mahjong/check-winning-v2`
*   **Method:** `POST`
*   **Content-Type:** `application/json`

## 3. 請求參數

### JSON Body

| 參數名             | 類型              | 必填 | 說明                                     |
|:----------------|:----------------|:---|:---------------------------------------|
| `hand`          | `array<string>` | 是  | 包含 17 張牌的陣列（含胡的那張牌）。                   |

### 牌的表示法 (Tile Notation)

延續 1.0.0 版本：
*   **萬子:** `1w`~`9w`, **筒子:** `1t`~`9t`, **條子:** `1s`~`9s`
*   **字牌:** `east`, `south`, `west`, `north`, `red`, `green`, `white`

## 4. 回應格式

### 成功回應 (胡牌)

*   **Status Code:** `200 OK`

```json
{
  "is_winning": true,
  "message": "恭喜胡牌！",
  "total_tai": 8,
  "tai_details": [
    {"name": "底", "tai": 1},
    {"name": "碰碰胡", "tai": 4}
  ],
  "combination": {
    "mianzi": [
      ["1s", "1s", "1s"],
      ["9s", "9s", "9s"],
      ["east", "east", "east"],
      ["west", "west", "west"],
      ["red", "red", "red"]
    ],
    "pair": ["white", "white"]
  }
}
```

### 成功回應 (未胡牌)

*   **Status Code:** `200 OK`

```json
{
  "is_winning": false,
  "message": "尚未胡牌。",
  "total_tai": 0,
  "reason": "缺乏對子或面子組合不正確。"
}
```

## 5. 台數計算標準 (參考)

API 將根據以下常見台式麻將規則計算（具體實作可調整）：
*   **1 台:** 自摸、門清、聽牌、平胡。
*   **2 台:** 全求人、花槓。
*   **4 台:** 碰碰胡、小三元、混一色。
*   **8 台:** 大三元、小四喜、清一色。
*   **16 台:** 大四喜。

## 6. 範例案例 (Example Case)

### 案例 1：大三元 碰碰胡

*   **情境:** 胡牌，手牌包含中發白刻子。
*   **請求:**
```json
{
  "hand": ["red", "red", "red", "green", "green", "green", "white", "white", "white", "1w", "1w", "1w", "2t", "2t", "2t", "9s", "9s"]
}
```
*   **回應:**
```json
{
  "is_winning": true,
  "total_tai": 13,
  "tai_details": [
    {"name": "底", "tai": 1},
    {"name": "大三元", "tai": 8},
    {"name": "碰碰胡", "tai": 4}
  ],
  "combination": {
    "mianzi": [
      ["red", "red", "red"],
      ["green", "green", "green"],
      ["white", "white", "white"],
      ["1w", "1w", "1w"],
      ["2t", "2t", "2t"]
    ],
    "pair": ["9s", "9s"]
  }
}
```

### 案例 2：清一色 平胡 (自摸)

*   **情境:** 全手牌皆為筒子，且全為順子，無花牌，無刻子。
*   **請求:**
```json
{
  "hand": ["1t", "2t", "3t", "2t", "3t", "4t", "4t", "5t", "6t", "5t", "6t", "7t", "7t", "8t", "9t", "1t", "1t"]
}
```
*   **回應:**
```json
{
  "is_winning": true,
  "total_tai": 12,
  "tai_details": [
    {"name": "底", "tai": 1},
    {"name": "清一色", "tai": 8},
    {"name": "平胡", "tai": 1},
    {"name": "門清", "tai": 1},
    {"name": "自摸", "tai": 1}
  ],
  "combination": {
    "mianzi": [
      ["1t", "2t", "3t"],
      ["2t", "3t", "4t"],
      ["4t", "5t", "6t"],
      ["5t", "6t", "7t"],
      ["7t", "8t", "9t"]
    ],
    "pair": ["1t", "1t"]
  }
}
```

### 案例 3：小四喜 混一色

*   **情境:** 包含東南西刻子，北風對子，其餘為條子組合。
*   **請求:**
```json
{
  "hand": ["east", "east", "east", "south", "south", "south", "west", "west", "west", "1s", "2s", "3s", "7s", "8s", "9s", "north", "north"]
}
```
*   **回應:**
```json
{
  "is_winning": true,
  "total_tai": 15,
  "tai_details": [
    {"name": "底", "tai": 1},
    {"name": "小四喜", "tai": 8},
    {"name": "混一色", "tai": 4}
  ],
  "combination": {
    "mianzi": [
      ["east", "east", "east"],
      ["south", "south", "south"],
      ["west", "west", "west"],
      ["1s", "2s", "3s"],
      ["7s", "8s", "9s"]
    ],
    "pair": ["north", "north"]
  }
}
```

### 案例 4：失敗案例 (詐胡 - 組合錯誤)

*   **情境:** 17 張牌但無法組成五面子一對子。
*   **請求:**
```json
{
  "hand": ["1w", "1w", "1w", "2w", "2w", "2w", "3w", "3w", "3w", "east", "east", "east", "red", "red", "red", "white", "red"]
}
```
*   **回應:**
```json
{
  "is_winning": false,
  "message": "尚未胡牌。",
  "total_tai": 0,
  "reason": "手牌無法組成合法的五面子一對子。"
}
```
