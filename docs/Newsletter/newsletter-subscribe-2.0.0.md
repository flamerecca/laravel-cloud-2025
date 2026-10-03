## 訂閱電子報頁面 (Newsletter Subscription Page)

### 概述

延續 [docs/Newsletter/newsletter-subscribe-1.0.0.md](newsletter-subscribe-1.0.0.md) 的頁面與提交流程設計，本版變更訂閱資料的儲存方式：訂閱名單不再透過 n8n Webhook 轉發保存，改用 [amazingbv/laravel-google-sheets-database-driver](https://packagist.org/packages/amazingbv/laravel-google-sheets-database-driver) 套件，由 Laravel 直接把訂閱者寫入一份 Google 試算表。

> 差異說明：1.0.0 版的決策是「Laravel 不保存訂閱名單，全權交由 n8n 管理」，理由是避免 Laravel 與 n8n 兩邊重複維護名單。本版改為 Laravel 直接寫入 Google Sheet，此 Google Sheet 本身就是唯一的訂閱名單來源，因此不再有雙邊維護的疑慮；同時 Google Sheet 對於非技術人員檢視訂閱名單也更直接。此設計差異以下方「已確定設計決策」第 1 項記錄，n8n Webhook 轉發機制自此版本起移除。

### 頁面位置

沿用 1.0.0 版，無變更：

- 路由：`GET /`，路由名稱 `home`，定義於 `routes/web/web.php`
- View：`resources/views/welcome.blade.php`

### 技術架構

- 套件：`amazingbv/laravel-google-sheets-database-driver`，將一份 Google 試算表當作資料庫使用：每個分頁 (tab) 對應一張資料表，第一列為欄位名稱
- 新增具名資料庫連線 `google-sheets`（定義於 `config/database.php`），與預設連線（本專案為 `sqlite`／正式環境的既有連線）並存，僅由電子報相關的 Model／Migration 明確指定使用，不影響應用程式其餘功能
- `App\Models\NewsletterSubscriber`：`protected $connection = 'google-sheets'`，`protected $table = 'newsletter_subscribers'`
- 提交路由、`SubscribeNewsletterRequest`、`throttle:6,1` 限流沿用 1.0.0 版決策，未變更
- `App\Jobs\StoreNewsletterSubscriber`（`ShouldQueue`）取代原本的 `ForwardNewsletterSubscription`：帶入 `email` 與 `subscribedAt`，於 `handle()` 呼叫 `NewsletterSubscriber::firstOrCreate(['email' => ...], ['subscribed_at' => ...])`，以 Job 內的 `firstOrCreate` 做應用層去重，避免同一 Email 建立多筆分頁資料列
- Google Sheets 寫入一樣透過佇列非同步執行，理由與 1.0.0 版決策 3 相同：避免外部 API（此處為 Google Sheets API）延遲或配額限制直接拖慢使用者的請求

### 環境設定

需要以下環境變數（見 `.env.example`）：

| 變數                             | 說明                                                                          |
|:---------------------------------|:------------------------------------------------------------------------------|
| `GOOGLE_SHEETS_SPREADSHEET_ID`   | 目標 Google 試算表的 ID                                                       |
| `GOOGLE_SHEETS_CREDENTIALS_PATH` | Google 服務帳戶 JSON 金鑰檔的絕對路徑，**不可提交至 Git**，需存放在版本庫外部 |
| `GOOGLE_SHEETS_CACHE_STORE`      | 套件內部快取所使用的 Laravel Cache Store，預設 `file`                         |
| `GOOGLE_SHEETS_CACHE_TTL`        | 快取秒數，預設 `60`                                                           |

部署到有真實憑證的環境後，需手動執行一次：

```bash
php artisan sheets:install
php artisan migrate --path=database/migrations/google-sheets --database=google-sheets
```

`sheets:install` 會驗證試算表可存取，並建立套件內部使用的隱藏分頁；上述 migration 只放在 `database/migrations/google-sheets/` 目錄，刻意不與 `database/migrations/` 下既有的 sqlite/mysql 遷移混放，避免一般的 `php artisan migrate` 誤把這份遷移套用到預設資料庫連線。

服務帳戶設定步驟：於 Google Cloud 建立服務帳戶並下載 JSON 金鑰，將目標試算表分享給金鑰中的 `client_email`（編輯權限），JSON 檔案存放在版本庫外部並指向 `GOOGLE_SHEETS_CREDENTIALS_PATH`。

### 資料表

Google 試算表中的 `newsletter_subscribers` 分頁：

| 欄位          | 類型      | 說明                   |
|:--------------|:----------|:-----------------------|
| id            | bigint    | 主鍵，套件自動遞增管理 |
| email         | string    | 訂閱者 Email           |
| subscribed_at | timestamp | 訂閱時間               |
| created_at    | timestamp | 建立時間               |
| updated_at    | timestamp | 更新時間               |

套件不強制唯一約束、外鍵等關聯完整性（Google Sheets 底層無法保證），Email 去重改由 `StoreNewsletterSubscriber` Job 的 `firstOrCreate` 在應用層處理。

### 畫面需求

沿用 1.0.0 版，無變更。

### 提交流程

1. 使用者於首頁輸入 Email，點擊「訂閱」
2. 表單以 `POST /newsletter` 送出，帶 CSRF Token
3. `SubscribeNewsletterRequest` 驗證 Email 必填且格式正確
4. 驗證失敗：導回首頁並帶 `$errors`，頁面顯示紅色錯誤訊息
5. 驗證成功：Controller dispatch `StoreNewsletterSubscriber` Job，帶入 `email` 與 `subscribedAt`
6. Controller 立即以 Session Flash `success` 訊息重導回首頁，不等待 Google Sheets 寫入完成
7. 佇列 Worker 非同步執行 Job：以 `email` 查詢 `newsletter_subscribers` 分頁，若不存在才寫入新的一列；若寫入失敗（例如配額限制），依 Job 的 `tries`／`backoff` 設定自動重試，多次失敗後落入 `failed_jobs` 資料表，供後續人工查看

### 已確定設計決策

1. **訂閱資料直接寫入 Google Sheet，取代 n8n Webhook 轉發。** 原因：Google Sheet 本身即為唯一的訂閱名單來源，不再需要 Laravel／n8n 雙邊維護；非技術同仁也能直接開試算表檢視名單。此決策取代 [docs/Newsletter/newsletter-subscribe-1.0.0.md](newsletter-subscribe-1.0.0.md) 決策 1「全權交由 n8n 管理」。
2. **Email 去重改在應用層以 `firstOrCreate` 處理。** 原因：Google Sheets driver 不支援唯一約束，若不在寫入前查詢既有資料，重複送出會在分頁中留下多筆重複列。
3. **寫入 Google Sheet 沿用佇列非同步處理，Job 更名為 `StoreNewsletterSubscriber`。** 原因與 1.0.0 版決策 3 相同：避免外部 API 延遲或配額限制拖慢使用者請求；Google Sheets API 另有明確的讀寫配額限制，非同步搭配套件內建的重試機制更為必要。
4. **`google-sheets` 資料庫連線以具名連線存在，不變更 `DB_CONNECTION` 預設值。** 原因：專案其餘資料表仍使用原本的資料庫，僅電子報訂閱功能明確指定 `google-sheets` 連線，避免影響其他既有功能。
5. **Google Sheets 專用的 Migration 獨立放在 `database/migrations/google-sheets/` 目錄，須以 `--path` 與 `--database=google-sheets` 手動執行。** 原因：避免一般的 `php artisan migrate` 誤將此遷移套用到預設資料庫連線。

### 待確認事項

- 正式環境的 Google 服務帳戶憑證與試算表由誰建立、如何配發／輪替金鑰，屬於維運範疇，本規格不涵蓋。
- 是否需要針對 Google Sheets API 讀寫配額（`GOOGLE_SHEETS_READ_REQUESTS_PER_MINUTE` / `GOOGLE_SHEETS_WRITE_REQUESTS_PER_MINUTE`）建立監控或告警，目前僅沿用套件預設值。
- 是否需要提供退訂連結或退訂頁面？目前規格不包含退訂流程。

### 驗收條件

- [ ] 首頁可看到「訂閱電子報」表單，含 Email 欄位與「訂閱」按鈕
- [ ] 送出有效 Email 後，頁面顯示「感謝訂閱！請到信箱查看確認信」成功訊息
- [ ] 送出空白或格式錯誤的 Email，頁面顯示繁體中文錯誤訊息，且原輸入值透過 `old('email')` 保留
- [ ] 提交路由為 `POST /newsletter`，且受 CSRF 保護
- [ ] 呼叫寫入 Google Sheet 的行為透過佇列任務執行，可用 `Queue::fake()` 驗證有 dispatch `StoreNewsletterSubscriber`
- [ ] 訂閱成功後，`newsletter_subscribers`（`google-sheets` 連線）會新增一筆對應的訂閱者資料
- [ ] 同一個 Email 重複訂閱，不會在 `newsletter_subscribers` 建立第二筆資料
- [ ] 短時間內重複送出超過限流門檻會收到 429 回應
