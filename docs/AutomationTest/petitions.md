## 連署 API 自動化測試規格需求

### 總覽

此文件定義了 `/api/signatures` 端點的自動化測試案例，旨在驗證其功能、資料驗證和錯誤處理是否符合規格。

#### 測試案例

##### 測試案例 1：成功建立新的連署

- **目的**：驗證使用者可以成功提交一個新的連署，並收到正確的回應。
- **前置條件**：無。
- **步驟**：
    1. 發送一個 `POST` 請求到 `/api/signatures`。
    2. 請求主體（Request Body）包含以下 JSON 資料：
        ```json
        {
            "name": "測試者",
            "email": "test.user@example.com",
            "phone": "0912345678",
            "comment": "這是一個測試留言。"
        }
        ```
- **預期結果**：
    1.  HTTP 回應狀態碼為 `201 Created`。
    2.  回應的 `Content-Type` 為 `application/json`。
    3.  回應的 JSON 結構應包含 `id`, `name`, `email`, `phone`, `comment`, `created_at` 欄位。
    4.  回應的 `name`, `email`, `phone`, `comment` 欄位值應與請求發送的資料相符。
    5.  資料庫中應存在一筆與提交資料對應的新紀錄。

---

##### 測試案例 2：缺少必要欄位 - `name`

- **目的**：驗證當請求中缺少 `name` 欄位時，系統會回傳驗證錯誤。
- **前置條件**：無。
- **步驟**：
    1. 發送一個 `POST` 請求到 `/api/signatures`。
    2. 請求主體僅包含 `email`：
        ```json
        {
            "email": "no.name@example.com"
        }
        ```
- **預期結果**：
    1.  HTTP 回應狀態碼為 `422 Unprocessable Entity`。
    2.  回應的 JSON 結構應包含 `message` 與 `errors` 欄位。
    3.  `errors` 物件中應包含 `name` 鍵，其值為一個包含錯誤訊息（例如："The name field is required."）的陣列。

---

##### 測試案例 3：缺少必要欄位 - `email`

- **目的**：驗證當請求中缺少 `email` 欄位時，系統會回傳驗證錯誤。
- **前置條件**：無。
- **步驟**：
    1. 發送一個 `POST` 請求到 `/api/signatures`。
    2. 請求主體僅包含 `name`：
        ```json
        {
            "name": "無電郵測試者"
        }
        ```
- **預期結果**：
    1.  HTTP 回應狀態碼為 `422 Unprocessable Entity`。
    2.  回應的 JSON 結構應包含 `message` 與 `errors` 欄位。
    3.  `errors` 物件中應包含 `email` 鍵，其值為一個包含錯誤訊息（例如："The email field is required."）的陣列。

---

##### 測試案例 4：`email` 格式無效

- **目的**：驗證當 `email` 格式不正確時，系統會回傳驗證錯誤。
- **前置條件**：無。
- **步驟**：
    1. 發送一個 `POST` 請求到 `/api/signatures`。
    2. 請求主體的 `email` 欄位為無效格式：
        ```json
        {
            "name": "測試者",
            "email": "invalid-email-format"
        }
        ```
- **預期結果**：
    1.  HTTP 回應狀態碼為 `422 Unprocessable Entity`。
    2.  回應的 JSON 結構應包含 `message` 與 `errors` 欄位。
    3.  `errors` 物件中應包含 `email` 鍵，其值為一個包含錯誤訊息（例如："The email must be a valid email address."）的陣列。

---

##### 測試案例 5：重複使用 `email` 進行連署

- **目的**：驗證同一個 `email` 無法重複連署。
- **前置條件**：資料庫中已存在一筆 `email` 為 `duplicate.user@example.com` 的連署紀錄。
- **步驟**：
    1.  （前置步驟）先建立一筆 `email` 為 `duplicate.user@example.com` 的連署。
    2.  再次發送一個 `POST` 請求到 `/api/signatures`，使用相同的 `email`：
        ```json
        {
            "name": "重複的測試者",
            "email": "duplicate.user@example.com"
        }
        ```
- **預期結果**：
    1.  HTTP 回應狀態碼為 `422 Unprocessable Entity`。
    2.  回應的 JSON 結構應包含 `error` 欄位。
    3.  `error` 欄位的值應為 `"This email has already been used to sign."`。
    4.  資料庫中不應新增任何紀錄。
