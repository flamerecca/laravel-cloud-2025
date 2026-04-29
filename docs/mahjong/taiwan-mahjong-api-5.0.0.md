# 台式麻將胡牌判斷與進階分析 API 5.0.0 規格書

本文件定義了用於判斷台式麻將（16 張）是否胡牌、計算台數，以及 5.0.0 版本新增的「打牌建議分析」API 規格。此版本旨在幫助玩家判斷目前手牌打出哪一張牌能獲得最高的胡牌率（聽牌廣度與剩餘張數）。

## 1. 概述

台式麻將 5.0.0 版本 API 在 4.0.0 的基礎上，新增了「打牌建議 (Discard Suggestion)」功能。當玩家手持 17 張牌（剛摸牌或起手）時，API 可分析打出每一張牌後進入「聽牌」狀態的可能性，並計算所聽牌張的剩餘數量，以此推算胡牌機率。

## 2. API 端點

### 2.1 判斷胡牌與計算台數 (v5)
*   **URL:** `/api/mahjong/v5/check-winning`
*   **Method:** `POST`
*   **說明:** 延續 v4 功能，支援所有台數計算。

### 2.2 打牌建議分析 (v5)
*   **URL:** `/api/mahjong/v5/analyze-discard`
*   **Method:** `POST`
*   **Content-Type:** `application/json`

## 3. 請求參數 (打牌建議分析)

### JSON Body

| 參數名                 | 類型              | 必填 | 說明                              |
|:--------------------|:----------------|:---|:--------------------------------|
| `hand`              | `array<string>` | 是  | 目前手持的 17 張牌。                    |
| `discards_on_table` | `array<string>` | 否  | 檯面上已打出的牌（含所有人），用於精確計算剩餘張數。預設為空。 |
| `wind_of_round`     | `string`        | 是  | 當前場風。                           |
| `wind_of_seat`      | `string`        | 是  | 當前門風。                           |
| `flowers`           | `array<string>` | 否  | 持有的花牌。                          |

## 4. 回應格式 (打牌建議分析)

### 成功回應

*   **Status Code:** `200 OK`

```json
{
  "suggestions": [
    {
      "discard": "1w",
      "is_ting": true,
      "ting_tiles": [
        {"tile": "2w", "remaining": 3},
        {"tile": "5w", "remaining": 4}
      ],
      "total_remaining": 7,
      "description": "打出 1w 後聽 2w, 5w，共 7 張牌。"
    },
    {
      "discard": "9s",
      "is_ting": false,
      "waiting_for_ting": [
        {"tile": "3t", "improvement": "聽 1t, 4t"}
      ],
      "description": "打出 9s 後未聽牌，但摸到 3t 可聽牌。"
    }
  ],
  "best_discard": "1w"
}
```

## 5. 邏輯判斷標準

1.  **聽牌判斷 (Ting):** 遍歷手牌中的 17 張牌，模擬打出每一張後，手牌剩下 16 張。檢查若再加入哪一張牌（共 34 種牌型）會達成胡牌條件。
2.  **胡牌率計算:**
    *   計算所有能讓剩餘 16 張牌胡牌的「聽牌張」。
    *   **剩餘張數 (Remaining):** `4 - (手牌中該張數量) - (檯面上該張數量)`。
    *   **總剩餘張數 (Total Remaining):** 所有聽牌張的剩餘數量總和。
3.  **最佳建議 (Best Discard):** 優先選擇 `total_remaining` 最高的選項。

## 6. 範例案例

### 案例 1：計算最佳打法 (聽兩頭)
*   **說明:** 玩家手牌中有 `1w, 2w, 3w, 4w` 及其它刻子，打出 `4w` 聽 `1w, 4w` 或打出 `1w` 聽 `2w, 5w`。
*   **請求:**
```json
{
  "hand": ["1w", "2w", "3w", "4w", "1t", "1t", "1t", "2s", "2s", "2s", "east", "east", "east", "red", "red", "red", "white"],
  "discards_on_table": ["1w", "1w", "5w"],
  "wind_of_round": "east",
  "wind_of_seat": "east"
}
```
*   **預期分析:**
    *   打出 `white`: 不聽牌。
    *   打出 `1w`: 剩下 `2w, 3w, 4w`，聽 `2w, 5w`。
        *   `2w` 剩餘: 4 - 1(手) = 3
        *   `5w` 剩餘: 4 - 0(手) - 1(桌) = 3
        *   總計: 6 張。
    *   **最佳打法:** `1w` (假設其它打法張數較少)。

### 案例 2：未聽牌但分析進張
*   **說明:** 手牌距離聽牌還差一進。API 應提示打出哪張後，摸到什麼牌可以聽牌。
*   **請求:**
```json
{
  "hand": ["1w", "2w", "4w", "5w", "1t", "2t", "4t", "5t", "east", "east", "east", "south", "south", "south", "red", "red", "red"],
  "wind_of_round": "east",
  "wind_of_seat": "east"
}
```
*   **回應內容:** 顯示各打法後的「一進聽」路徑。

### 案例 3：檯面已打過的牌影響剩餘張數 (精確計算)
*   **說明:** 玩家聽 `3w, 6w`，但檯面上 `3w` 已經被打掉 3 張，`6w` 沒出現過。API 應準確計算剩餘總數為 5 張。
*   **請求:**
```json
{
  "hand": ["4w", "5w", "1t", "2t", "3t", "4s", "5s", "6s", "east", "east", "east", "south", "south", "south", "west", "west", "west"],
  "discards_on_table": ["3w", "3w", "3w", "1w", "2w"],
  "wind_of_round": "south",
  "wind_of_seat": "west"
}
```
*   **預期分析:**
    *   打出任何一張湊不出對子的牌（如 `west`）後聽 `3w, 6w`。
    *   `3w` 剩餘: 4 - 0(手) - 3(桌) = 1。
    *   `6w` 剩餘: 4 - 0(手) - 0(桌) = 4。
    *   `total_remaining`: 5。

### 案例 4：三面聽與對倒的決策
*   **說明:** 玩家手牌 `2w, 3w, 4w, 5w, 6w` (聽 1, 4, 7w) 與另一組對子 `1t, 1t` 及 `2t, 2t` (對倒)。比較哪種打法剩餘張數更多。
*   **請求:**
```json
{
  "hand": ["2w", "3w", "4w", "5w", "6w", "1t", "1t", "2t", "2t", "1s", "1s", "1s", "red", "red", "red", "white", "white"],
  "wind_of_round": "east",
  "wind_of_seat": "north"
}
```
*   **預期回應:** API 會列出打出 `1t` (聽 2t 與 1, 4, 7w 複合型) 或打出其它牌的聽牌廣度。

### 案例 5：地獄單釣 (剩餘張數為 0)
*   **說明:** 玩家聽單釣 `white`，但 `white` 已經全部出現在檯面或玩家手中。
*   **請求:**
```json
{
  "hand": ["1w", "2w", "3w", "4w", "5w", "6w", "7s", "8s", "9s", "east", "east", "east", "red", "red", "red", "white", "white"],
  "discards_on_table": ["white", "white"],
  "wind_of_round": "east",
  "wind_of_seat": "east"
}
```
*   **預期回應:** `is_ting: true`, 但 `total_remaining: 0`。API 應在描述中提醒「此牌已無剩餘，建議更換打法」。
