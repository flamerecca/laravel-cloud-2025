# 定期寄送電子報設計規格

日期：2026-10-12
狀態：待審閱

## 1. 目標

讓管理者在 Filament 後台維護每一期電子報的內容與預計寄送時間，系統依排程自動透過 Mailgun，把到期的那一期寄給該主題下已確認且未退訂的訂閱者。第一個使用情境是「Filament 進階分享」，一週一期，共 12 期。

### 成功標準

- 後台可新增、編輯期數，並設定預計寄送時間。
- 到了預計寄送時間，該期只會被寄出一次，且只寄給同主題、已確認、未退訂的訂閱者。
- 同一位訂閱者不會收到同一期兩次，即使工作失敗後重試。
- 每封信都有簽章的退訂連結，點擊後該訂閱者不再收到信。
- 本機與測試不會真的呼叫 Mailgun。

### 範圍外

- 首頁的一般電子報訂閱者，也就是 `topic` 為 null 的資料，這次不寄送。
- 開信率、點擊率、退信處理與 Mailgun Webhooks。
- Laravel 進階分享主題沒有期數內容，這次不匯入。

## 2. 現況

- `symfony/mailgun-mailer` 與 `symfony/http-client` 已列在 `composer.json`，也已提交，不需要再安裝。
- `config/services.php` 與 `config/mail.php` 已有 `mailgun` 設定，`.env.example` 已有 `MAILGUN_DOMAIN`、`MAILGUN_SECRET`、`MAILGUN_ENDPOINT`。
- `NewsletterSubscriber` 已有 `email`、`topic`、`subscribed_at`、`confirmed_at`，主題以 `NewsletterTopic` 列舉轉型。
- 佇列使用 `database` 驅動，`routes/console.php` 目前沒有任何排程。
- `docs/FilamentNewsletter/` 有 12 份期數正文，檔名格式為「第 NN 期：標題.md」，前面帶有 `date`、`category`、`summary`、`tags` 的 front matter。

## 3. 資料模型

### 3.1 `newsletter_issues`

| 欄位 | 型別 | 說明 |
| :--- | :--- | :--- |
| `id` | bigint | 主鍵 |
| `topic` | string | 對應 `NewsletterTopic` 的值 |
| `number` | unsigned integer | 期號，同一主題內唯一 |
| `title` | string | 信件標題 |
| `summary` | text，可為空 | 摘要，用於信件預覽文字 |
| `body` | longText | Markdown 內文 |
| `scheduled_at` | timestamp，可為空 | 預計寄送時間，空值代表尚未排程 |
| `dispatched_at` | timestamp，可為空 | 開始派送的時間，用來避免重複派送 |
| `sent_at` | timestamp，可為空 | 全部寄送工作完成的時間 |
| `created_at` / `updated_at` | timestamp | 一般時間戳記 |

唯一索引：`topic` 加 `number`。

### 3.2 `newsletter_deliveries`

| 欄位 | 型別 | 說明 |
| :--- | :--- | :--- |
| `id` | bigint | 主鍵 |
| `newsletter_issue_id` | foreign key | 所屬期數，刪除期數時連帶刪除 |
| `newsletter_subscriber_id` | foreign key | 收件的訂閱者，刪除訂閱者時連帶刪除 |
| `sent_at` | timestamp | 寄出時間 |

唯一索引：`newsletter_issue_id` 加 `newsletter_subscriber_id`，作為不重複寄送的最終保證。

### 3.3 `newsletter_subscribers` 調整

新增 `unsubscribed_at` timestamp，可為空。「可寄送」的定義為：`confirmed_at` 不為空，且 `unsubscribed_at` 為空。

在 `NewsletterSubscriber` 加上 `scopeDeliverable`，統一這個條件。

## 4. 寄送流程

1. 排程器每分鐘執行 `newsletter:send-due`，並設定 `withoutOverlapping`。
2. 指令查詢 `scheduled_at <= now()`、`dispatched_at` 為空的期數，逐期處理。
3. 處理一期時，先以單一更新語句把 `dispatched_at` 寫入並確認影響筆數為 1，搶不到的程序直接略過，避免兩個程序同時派送。
4. 以 `chunkById` 取得該主題下可寄送的訂閱者，每位派送一個 `SendNewsletterIssue` 佇列工作。
5. `SendNewsletterIssue` 執行時：
   - 若 `newsletter_deliveries` 已有該組合，直接結束。
   - 若訂閱者此時已退訂，直接結束。
   - 寄出 `NewsletterIssueMail`，成功後寫入 `newsletter_deliveries`。
6. 工作以 Bus Batch 派送，並呼叫 `allowFailures()`。批次結束後，不論是否有失敗工作，都由 `finally` 回呼寫入該期 `sent_at`。批次所需的 `job_batches` 資料表已包含在 Laravel 預設的佇列遷移中，實作時先確認專案已有此資料表。

失敗處理：工作可重試，重試時由步驟 5 的檢查保證不會重複寄送。失敗的工作留在 `failed_jobs`，由管理者從失敗列表處理，不會阻擋該期標記為已寄出。後台的「已寄出人數」以 `newsletter_deliveries` 的筆數計算，因此失敗人數可由可寄送人數減去已寄出人數得知。

## 5. 信件

- `NewsletterIssueMail` 為 Markdown Mailable，沿用現有確認信的版型。
- 主旨為期數標題。預覽文字使用 `summary`。
- 內文由 `body` 轉成 HTML，轉換使用 Laravel 內建的 `Str::markdown`，並開啟 `html_input => strip` 與 `allow_unsafe_links => false`。
- 信件底部固定放簽章的退訂連結，使用 `URL::signedRoute`，不設過期時間。
- 寄件者沿用 `MAIL_FROM_ADDRESS` 與 `MAIL_FROM_NAME`。

## 6. 退訂

- 新增 `GET /newsletter/unsubscribe`，路由名稱 `newsletter.unsubscribe`，參數為 `email` 與 `topic`，需通過簽章驗證，並套用 `throttle:6,1`。
- 簽章有效時，把對應訂閱者的 `unsubscribed_at` 寫入現在時間，顯示「已取消訂閱」頁面。
- 簽章無效回 403，找不到訂閱者回 404，行為與現有確認連結一致。
- 重複點擊視為成功，不覆寫第一次的退訂時間。
- 退訂後若同一個 Email 重新到訂閱頁訂閱，既有的 `StoreNewsletterSubscriber` 流程要把 `unsubscribed_at` 清空並重新寄出確認信，確認後才恢復可寄送。

## 7. 後台

新增 Filament Resource「電子報期數」，放在現有「電子報訂閱者」旁邊。

- 列表欄位：主題、期號、標題、預計寄送時間、狀態、已寄出人數。
- 狀態由欄位推導：未排程、已排程、派送中、已寄出。
- 表單欄位：主題、期號、標題、摘要、Markdown 內文、預計寄送時間。
- 已派送的期數，也就是 `dispatched_at` 不為空，內文與預計寄送時間在表單中唯讀，避免寄出後被改動。
- 篩選器：主題、狀態。

## 8. 匯入指令

`newsletter:import-issues {path}`，預設路徑為 `docs/FilamentNewsletter`。

- 讀取目錄下檔名符合「第 NN 期：標題.md」的檔案，不含規格書子目錄。
- 期號取自檔名，標題取自檔名，摘要取自 front matter 的 `summary`，內文為 front matter 之後的 Markdown，並移除開頭重複的一級標題。
- 主題固定為 `filament`。
- 以 `topic` 加 `number` 為鍵做 upsert，重複執行不會產生重複資料，且不會覆寫已派送期數。
- 匯入的期數 `scheduled_at` 保持空值，由管理者在後台排定。

## 9. Mailgun 設定

- 正式環境設定 `MAIL_MAILER=mailgun`、`MAILGUN_DOMAIN`、`MAILGUN_SECRET`、`MAILGUN_ENDPOINT`。
- `.env.example` 補上註解，說明正式環境需要把 `MAIL_MAILER` 改為 `mailgun`，預設仍為 `log`。
- 不修改 `composer.json`，不重新安裝任何套件。

## 10. 測試

所有測試使用 `Mail::fake()` 與 `Bus::fake()` 或 `Queue::fake()`，不呼叫 Mailgun。

- 只派送 `scheduled_at` 已到且未派送的期數。
- 尚未到時間或未排程的期數不派送。
- 只寄給同主題、已確認、未退訂的訂閱者。
- 同一期執行兩次 `newsletter:send-due`，每位訂閱者只收到一封。
- 工作重試時，已有 `newsletter_deliveries` 紀錄的組合不會再寄。
- 訂閱者在派送後、寄出前退訂，不會收到信。
- 退訂連結：有效簽章成功、竄改簽章 403、找不到訂閱者 404、重複點擊不覆寫時間。
- 退訂後重新訂閱，需重新確認才會恢復可寄送。
- 匯入指令：正確解析期號、標題、摘要與內文，重複執行不產生重複資料，不覆寫已派送期數。
- 後台 Resource：可建立與編輯期數，已派送期數的內文唯讀。
- 內文轉換會移除危險的 HTML。

## 11. 檔案清單

新增：
- 遷移：`create_newsletter_issues_table`、`create_newsletter_deliveries_table`、`add_unsubscribed_at_to_newsletter_subscribers_table`
- 模型與工廠：`NewsletterIssue`、`NewsletterDelivery`
- 指令：`SendDueNewsletterIssues`、`ImportNewsletterIssues`
- 工作：`SendNewsletterIssue`
- 信件：`NewsletterIssueMail` 與對應的 Markdown 視圖
- 控制器：`NewsletterUnsubscribeController`，以及退訂結果視圖
- Filament：`NewsletterIssues` Resource
- 測試：對應上述各項

修改：
- `NewsletterSubscriber`：新增 `unsubscribed_at` 轉型與 `scopeDeliverable`
- `StoreNewsletterSubscriber`：重新訂閱時清空 `unsubscribed_at`
- `routes/console.php`：加入每分鐘排程
- `routes/web/newsletter.php`：加入退訂路由
- `.env.example`：補上 Mailgun 說明

## 12. 待確認事項

目前沒有未決事項。若審閱時發現假設不符，優先檢查第 1 節「範圍外」與第 6 節的重新訂閱行為。
