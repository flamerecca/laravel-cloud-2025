## 主題電子報訂閱 (Topic Newsletter Subscription)

### 概述

延續 [docs/Newsletter/newsletter-subscribe-2.0.0.md](newsletter-subscribe-2.0.0.md) 的 Google Sheets 儲存架構，本版新增「主題電子報」：每個主題有自己的訂閱頁面，訂閱者也寫入 Google 試算表中該主題專屬的分頁。首頁的一般電子報訂閱維持不變，仍寫入 `newsletter_subscribers` 分頁。

第一個主題為「Filament 進階分享」。

### 頁面位置

- 路由：`GET /newsletter/{topic}`，路由名稱 `newsletter.show`，定義於 `routes/web/newsletter.php`
- `{topic}` 以 `App\Enums\NewsletterTopic` 做 Enum 路由綁定，不存在的主題回傳 404
- View：`resources/views/newsletter/show.blade.php`，所有主題共用同一個 View
- 目前主題：`/newsletter/filament`

### 技術架構

- `App\Enums\NewsletterTopic`：主題的唯一定義來源，每個 case 提供
  - `title()`：頁面標題
  - `description()`：頁面簡介
  - `highlights()`：「你會收到的內容」清單
  - `table()`：對應的 Google Sheets 分頁名稱，格式為 `newsletter_{value}_subscribers`
- 表單仍送往既有的 `POST /newsletter`，以隱藏欄位 `topic` 帶入主題值；未帶 `topic` 即為一般電子報
- `SubscribeNewsletterRequest` 新增規則 `topic`：`nullable`、`Rule::enum(NewsletterTopic::class)`
- `StoreNewsletterSubscriber` Job 新增可選參數 `?NewsletterTopic $topic`，以 `NewsletterSubscriber::forTopic($topic)->firstOrCreate(...)` 寫入對應分頁
- `NewsletterSubscriber::forTopic()` 依主題切換 Model 的資料表；傳入 `null` 時使用預設的 `newsletter_subscribers`

### 資料表

每個主題一個 Google Sheets 分頁，欄位與 `newsletter_subscribers` 相同：

| 主題             | 分頁                              |
|:-----------------|:----------------------------------|
| 一般電子報       | `newsletter_subscribers`          |
| Filament 進階分享 | `newsletter_filament_subscribers` |

部署後需執行 Google Sheets 專用 Migration 建立新分頁：

```bash
php artisan migrate --path=database/migrations/google-sheets --database=google-sheets
```

### 新增主題的步驟

1. 在 `NewsletterTopic` 新增 case，並補上 `title()`、`description()`、`highlights()` 的對應內容
2. 在 `database/migrations/google-sheets/` 新增 Migration，以 `NewsletterTopic::Xxx->table()` 建立分頁
3. 部署後執行上方的 Migration 指令

### 已確定設計決策

1. **每個主題的訂閱者寫入各自的 Google Sheets 分頁，不使用 `topic` 欄位共用同一分頁。** 原因：非技術同仁可以直接打開對應分頁檢視單一主題的名單，不需要額外篩選。
2. **主題以 Enum 定義，不存資料庫。** 原因：每個主題都需要對應的分頁 Migration，本來就要改程式碼才能新增主題，Enum 讓路由綁定、確認規則與分頁名稱共用同一個來源。
3. **所有主題共用 `POST /newsletter` 與同一個限流規則 `throttle:6,1`。** 原因：沿用 2.0.0 版既有的提交流程，避免為每個主題重複定義路由。
4. **Email 去重以分頁為單位。** 同一個 Email 可以同時訂閱一般電子報與各主題電子報，但在同一分頁中只會有一筆。

### 待確認事項

- 主題頁面上的內容文案，即 `highlights()`，需由內容負責人確認。
- 首頁是否要放各主題訂閱頁面的連結，目前未提供入口。
- 退訂流程仍未定義，與 2.0.0 版相同。

### 驗收條件

- [ ] `/newsletter/filament` 顯示「Filament 進階分享」標題、內容清單與訂閱表單
- [ ] 不存在的主題頁面回傳 404
- [ ] 從主題頁面訂閱成功後，導回該主題頁面並顯示成功訊息
- [ ] 主題訂閱寫入該主題的分頁，不寫入 `newsletter_subscribers`
- [ ] 同一個 Email 可以分別訂閱一般電子報與主題電子報，同一分頁不會重複
- [ ] 送出不存在的主題會顯示確認錯誤，且不派送 Job
- [ ] 首頁一般電子報訂閱行為與 2.0.0 版相同
