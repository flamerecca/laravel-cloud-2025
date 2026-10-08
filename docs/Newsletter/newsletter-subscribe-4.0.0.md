## 電子報訂閱改存資料庫與雙重確認 (Database Storage & Double Opt-In)

### 概述

延續 [docs/Newsletter/newsletter-subscribe-3.0.0.md](newsletter-subscribe-3.0.0.md) 的主題電子報設計，本版有兩項變更：

1. 移除 `amazingbv/laravel-google-sheets-database-driver` 套件，訂閱者改為直接寫入應用程式的預設資料庫連線。
2. 新增雙重確認，即 Double Opt-In：訂閱後寄出確認信，訂閱者點擊信中的確認連結後，才算訂閱成功。

> 差異說明：2.0.0 版與 3.0.0 版以 Google 試算表作為唯一的訂閱名單來源。本版改回資料庫，Google Sheets 連線、專用遷移目錄與 `GOOGLE_SHEETS_*` 環境變數全數移除。3.0.0 版決策 1「每個主題各自一個分頁」也一併改為單一資料表，見下方「已確定設計決策」第 1 項。

### 路由

定義於 `routes/web/newsletter.php`：

| 方法 | 路徑 | 路由名稱 | 用途 |
| :--- | :--- | :--- | :--- |
| `GET` | `/newsletter` | `newsletter.index` | 電子報列表頁面 |
| `GET` | `/newsletter/confirm` | `newsletter.confirm` | 確認信中的確認連結 |
| `GET` | `/newsletter/{topic}` | `newsletter.show` | 主題訂閱頁面 |
| `POST` | `/newsletter` | `newsletter.subscribe` | 送出訂閱表單 |

`/newsletter` 列表頁面列出 `NewsletterTopic` 的所有主題，連到各自的訂閱頁面，最後一項為連到首頁的一般電子報。View 為 `resources/views/newsletter/index.blade.php`，新增主題時會自動出現在列表中。

`newsletter.confirm` 必須定義在 `newsletter.show` 之前，否則 `confirm` 會被當成主題做列舉綁定而回傳 404。

### 資料表

所有訂閱者存在同一張 `newsletter_subscribers` 資料表，遷移檔為 `database/migrations/2026_10_08_113442_create_newsletter_subscribers_table.php`：

| 欄位 | 型別 | 說明 |
| :--- | :--- | :--- |
| `id` | `bigint` | 主鍵 |
| `email` | `string` | 訂閱者 Email |
| `topic` | `string`，可為 `null` | `null` 代表一般電子報，其餘為 `NewsletterTopic` 的值 |
| `subscribed_at` | `timestamp` | 送出訂閱的時間 |
| `confirmed_at` | `timestamp`，可為 `null` | 點擊確認連結的時間，`null` 代表尚未確認 |
| `created_at` / `updated_at` | `timestamp` | |

並建立 `['email', 'topic']` 的唯一索引。

### 技術架構

- `App\Models\NewsletterSubscriber`
  - 使用預設資料庫連線，`topic` 轉型為 `NewsletterTopic` 列舉
  - `forTopic(?NewsletterTopic $topic)`：以 `withAttributes()` 篩選指定主題的訂閱者，傳入 `null` 時為一般電子報；透過此查詢建立的資料也會自動帶入相同的 `topic`
  - `isConfirmed()`：`confirmed_at` 是否有值
- `App\Jobs\StoreNewsletterSubscriber`：以 `forTopic($topic)->firstOrCreate()` 寫入訂閱者，若尚未確認，接著寄出確認信；已確認的訂閱者重複訂閱時不再寄信
- `App\Mail\NewsletterConfirmation`：確認信，信中的連結由 `URL::temporarySignedRoute()` 產生，帶入 `email` 與 `topic`，7 天後失效，天數定義於 `LINK_EXPIRES_IN_DAYS`
- `App\Http\Controllers\NewsletterConfirmationController`：確認簽章後把 `confirmed_at` 設為目前時間，並顯示 `resources/views/newsletter/confirmed.blade.php`
  - 簽章無效或已過期：回傳 403，顯示「確認連結無效」
  - 簽章有效但找不到訂閱者：回傳 404，顯示「確認連結無效」
- 送出訂閱後的成功訊息改為「感謝訂閱！請到信箱點擊確認連結，完成訂閱」

### 後台管理

Filament 後台 `/admin/newsletter-subscribers` 提供電子報訂閱者管理，Resource 為 `App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource`，與後台其他頁面相同，只有 `User::canAccessPanel()` 允許的帳號可以進入。

- 列表：Email、電子報、確認時間、訂閱時間，預設依訂閱時間由新到舊排序；可搜尋 Email，可依電子報主題篩選，包含一般電子報，也可依確認狀態篩選
- 重寄確認信：只對尚未確認的訂閱者顯示，重新派送 `StoreNewsletterSubscriber`
- 新增訂閱者：輸入 Email 並選擇電子報，未選擇即為一般電子報；與前台表單相同，會寄出確認信，對方點擊確認連結後才算訂閱成功
- 刪除：單筆刪除與批次刪除

後台刻意不提供編輯功能，也不提供手動標記為已確認。原因：修改已確認訂閱者的 Email，或由管理員代為確認，都會讓沒有親自點擊確認連結的 Email 成為已確認的訂閱者，違反雙重確認的目的。

### 環境設定

- 移除所有 `GOOGLE_SHEETS_*` 環境變數。
- 寄信使用 Mailgun，需設定 `MAIL_MAILER=mailgun`、`MAILGUN_DOMAIN`、`MAILGUN_SECRET`、`MAIL_FROM_ADDRESS`；帳號在歐洲區時，`MAILGUN_ENDPOINT` 改為 `api.eu.mailgun.net`。
- `APP_URL` 必須是使用者實際造訪的網址，包含 Port。確認連結的簽章涵蓋完整網址，`APP_URL` 錯誤會讓連結無法開啟或簽章確認失敗。

部署後執行一般的遷移指令即可：

```bash
php artisan migrate
```

### 新增主題的步驟

1. 在 `NewsletterTopic` 新增 case，並補上 `title()`、`description()`、`highlights()` 的對應內容。

不再需要新增遷移檔。

### 已確定設計決策

1. **所有主題共用 `newsletter_subscribers` 資料表，以 `topic` 欄位區分，取代 3.0.0 版的每個主題一個分頁。** 原因：3.0.0 版分頁設計的理由是讓非技術同仁直接打開分頁檢視名單，改存資料庫後這個理由不再成立；每個主題一張資料表反而讓新增主題都要寫遷移檔。
2. **主題仍以列舉定義，不存資料庫。** 沿用 3.0.0 版決策 2。
3. **確認連結使用帶簽章的網址，不在資料庫儲存確認標記。** 原因：簽章已能防止竄改 Email 或主題，也能設定失效時間，不需要多一個欄位與過期清理機制。
4. **一般電子報與各主題的訂閱各自確認。** 同一個 Email 訂閱多個主題時，會收到多封確認信，確認其中一封不影響其他訂閱。
5. **寄送確認信仍在佇列 Job 中執行。** 原因：Mailgun API 的延遲不應拖慢使用者送出表單的回應。

### 已知限制

- `['email', 'topic']` 唯一索引對 `topic` 為 `null` 的一般電子報無效，因為 SQLite 與 MySQL 都把多個 `null` 視為不同的值。一般電子報的去重目前只靠 Job 內的 `firstOrCreate`，在極短時間內重複送出時，理論上仍可能產生重複資料。
- 本版不搬移 Google 試算表中既有的訂閱資料。若正式環境曾經寫入過 Google Sheets，需要另外匯入。

### 待確認事項

- 正式環境的 Google 試算表中是否已有訂閱資料，以及是否需要匯入。
- 寄送電子報時必須只篩選 `confirmed_at` 不為 `null` 的訂閱者，目前尚未實作寄送電子報的功能。
- 首頁是否要放 `/newsletter` 列表頁面的連結，目前列表頁面沒有其他頁面連入。
- 退訂流程仍未定義。

### 驗收條件

- [ ] 送出訂閱後，`newsletter_subscribers` 新增一筆資料，`topic` 對應主題，`confirmed_at` 為 `null`
- [ ] 送出訂閱後寄出確認信，主旨為「請確認訂閱「主題名稱」」
- [ ] 點擊確認連結後顯示「訂閱成功」，且 `confirmed_at` 有值
- [ ] 被竄改或超過 7 天的確認連結顯示「確認連結無效」，且不會完成訂閱
- [ ] 已確認的訂閱者重複訂閱時，不會再收到確認信
- [ ] 同一個 Email 可以同時訂閱一般電子報與主題電子報，各自確認
- [ ] `/newsletter` 列出所有主題與一般電子報，點擊後進入對應的訂閱頁面
- [ ] 後台可以檢視、搜尋、篩選與刪除訂閱者，並可對尚未確認的訂閱者重寄確認信
- [ ] 後台新增的訂閱者在點擊確認連結前維持尚未確認
- [ ] 專案中不再有任何 Google Sheets 相關的設定、遷移檔或環境變數
