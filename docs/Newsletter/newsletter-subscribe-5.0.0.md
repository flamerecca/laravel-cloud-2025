## 電子報寄送、退訂與系列模式 (Delivery, Unsubscribe & Series Mode)

### 概述

延續 [docs/Newsletter/newsletter-subscribe-4.0.0.md](newsletter-subscribe-4.0.0.md) 的資料庫儲存與雙重確認設計，本版補上 4.0.0 版尚未實作的部分，並記錄目前的完整行為：

1. 電子報期數 `newsletter_issues`，由後台編輯，到了預計寄送時間自動寄出。
2. 寄送紀錄 `newsletter_deliveries`，並以 `send_round` 支援「改為未寄出」後重寄。
3. 退訂流程，信中有簽章連結與 `List-Unsubscribe` 標頭。
4. 系列模式：部署後才確認信箱的主題訂閱者，不論何時加入，都從第 1 期開始，依個人進度收到每一期。
5. 後台新增期數管理、系列寄送間隔設定、訂閱者進度與調整下一封。

> 差異說明：4.0.0 版「待確認事項」中的「寄送電子報」與「退訂流程」兩項已在本版完成。4.0.0 版「後台刻意不提供手動標記為已確認」的決定也已改變，見下方「後台管理」。詳細設計與取捨另見 `docs/superpowers/specs/2026-10-18-newsletter-series-mode-design.md`。

### 路由

定義於 `routes/web/newsletter.php`：

| 方法 | 路徑 | 路由名稱 | 用途 |
| :--- | :--- | :--- | :--- |
| `GET` | `/newsletter` | `newsletter.index` | 電子報列表頁面 |
| `GET` | `/newsletter/confirm` | `newsletter.confirm` | 確認信中的確認連結 |
| `GET` | `/newsletter/unsubscribe` | `newsletter.unsubscribe` | 退訂確認頁面，不寫入資料 |
| `POST` | `/newsletter/unsubscribe` | 無 | 實際退訂，也是 `List-Unsubscribe-Post` 一鍵退訂的目標 |
| `GET` | `/newsletter/{topic}` | `newsletter.show` | 主題訂閱頁面 |
| `POST` | `/newsletter` | `newsletter.subscribe` | 送出訂閱表單 |

- `confirm` 與 `unsubscribe` 必須定義在 `newsletter.show` 之前，否則會被當成主題的 slug。
- 確認與退訂路由都套用 `throttle:6,1`。
- `{topic}` 是主題的 slug，由 `NewsletterTopic::slug()` 定義，例如 Laravel 主題的 slug 為 `laravel-test`。資料庫與簽章連結仍存列舉的值 `laravel`。以列舉的值造訪時，會以 301 轉址到 slug。其餘主題的 slug 與值相同。
- 退訂用 `GET` 只顯示確認頁，因為信件掃描器會預先開啟信中所有連結，寫入必須由 `POST` 觸發。`List-Unsubscribe-Post` 的一鍵請求不帶 CSRF 標記，因此 `bootstrap/app.php` 把 `newsletter/unsubscribe` 排除在 CSRF 驗證之外，改由簽章作為憑證。

### 主題

`App\Enums\NewsletterTopic` 目前有五個主題：

| 值 | 標題 | slug |
| :--- | :--- | :--- |
| `filament` | Filament 進階分享 | `filament` |
| `laravel` | Laravel 自動化測試分享 | `laravel-test` |
| `spring-boot` | Spring Boot 搭配 Kotlin 基礎開發 | `spring-boot` |
| `ktor` | Ktor 基礎教學 | `ktor` |
| `exposed` | Kotlin Exposed 基礎教學 | `exposed` |

每個主題在列舉中提供 `title()`、`description()`、`logo()`、`keyPoints()`，部分主題另有 `secondaryLogo()` 與 `secondaryLogoLabel()`，Laravel 為 Pest，其餘 Kotlin 主題為 Kotlin。信件寄件者與名稱由 `config/mail.php` 的 `newsletter_from`、`newsletter_name` 提供，預設為 `newsletter@reccachao.net`、「Recca Chao 電子報」，可用 `NEWSLETTER_FROM_ADDRESS`、`NEWSLETTER_FROM_NAME`、`NEWSLETTER_NAME` 覆寫。

### 資料表

| 資料表 | 說明 | 遷移檔 |
| :--- | :--- | :--- |
| `newsletter_subscribers` | 訂閱者，沿用 4.0.0 版 | `2026_10_08_113442_create_newsletter_subscribers_table.php` |
| `newsletter_issues` | 電子報期數 | `2026_10_12_141518_create_newsletter_issues_table.php` |
| `newsletter_deliveries` | 寄送紀錄 | `2026_10_12_141519_create_newsletter_deliveries_table.php` |
| `newsletter_topic_settings` | 各主題的系列寄送間隔 | `2026_10_18_094056_create_newsletter_topic_settings_table.php` |

`newsletter_subscribers` 在 4.0.0 版之後新增的欄位：

| 欄位 | 型別 | 說明 |
| :--- | :--- | :--- |
| `unsubscribed_at` | `timestamp`，可為 `null` | 點擊退訂連結的時間，`null` 代表未退訂 |
| `series_started_at` | `timestamp`，可為 `null` | 進入系列模式的時間，`null` 代表廣播模式 |
| `series_next_at` | `timestamp`，可為 `null` | 下一封最早可寄出的時間 |
| `series_start_number` | `unsignedInteger`，預設 `1` | 下一封只會從期號大於等於此值的期數挑選 |

`unsubscribed_at` 由 `2026_10_12_141451_add_unsubscribed_at_to_newsletter_subscribers_table.php` 新增，系列欄位由 `2026_10_18_094055_add_series_columns_to_newsletter_subscribers_table.php` 新增，後者並建立 `['series_started_at', 'series_next_at']` 索引。

`newsletter_issues`：

| 欄位 | 型別 | 說明 |
| :--- | :--- | :--- |
| `topic` | `string` | `NewsletterTopic` 的值 |
| `number` | `unsignedInteger` | 期號，`['topic', 'number']` 唯一 |
| `send_round` | `unsignedInteger`，預設 `1` | 寄送回合，每次「改為未寄出」加 1 |
| `title` / `summary` / `body` | `string` / `text` 可為 `null` / `longText` | 標題、摘要、Markdown 內文 |
| `scheduled_at` | `timestamp`，可為 `null` | 預計寄送時間，`null` 為草稿 |
| `dispatched_at` | `timestamp`，可為 `null` | 派送任務進入佇列的時間，用於避免重複派送 |
| `sent_at` | `timestamp`，可為 `null` | 整批寄送完成的時間 |

`newsletter_deliveries` 記錄「哪一期、哪位訂閱者、第幾回合」已寄出，`['newsletter_issue_id', 'newsletter_subscriber_id', 'send_round']` 唯一。`send_round` 由 `2026_10_12_155254_add_send_round_to_newsletter_issues_and_deliveries.php` 新增。

`newsletter_topic_settings`：`topic` 唯一，`interval_days` 為 `unsignedSmallInteger`，預設 7。沒有紀錄的主題一律使用 `NewsletterTopicSetting::DEFAULT_INTERVAL_DAYS`，即 7 天。

### 訂閱與確認

流程與 4.0.0 版相同，差異如下：

- `NewsletterSubscriber::confirm()` 統一處理確認，設定 `confirmed_at` 並清空 `unsubscribed_at`。若訂閱者有主題且尚未進入系列模式，同時設定 `series_started_at` 與 `series_next_at` 為目前時間，第 1 期因此立刻到期。一般電子報沒有期數，不進入系列模式。
- 信件中的確認連結 `NewsletterConfirmationController` 與後台「直接確認訂閱」，單筆與批次，都呼叫 `confirm()`。
- 退訂後重新訂閱，`StoreNewsletterSubscriber` 會把 `confirmed_at`、`unsubscribed_at`、`series_started_at`、`series_next_at` 全部清空，並重寄確認信，確認後重新進入系列模式。`series_start_number` 保留，管理員先前的調整仍有效，已收過的期數依寄送紀錄不會重寄。

### 退訂

- 每封電子報帶有簽章的退訂連結 `NewsletterSubscriber::unsubscribeUrl()`，並附上 `List-Unsubscribe` 與 `List-Unsubscribe-Post: List-Unsubscribe=One-Click` 標頭。
- `NewsletterUnsubscribeController::show` 簽章無效回傳 403，簽章有效顯示確認頁。`store` 簽章無效回傳 403，找不到訂閱者回傳 404，其餘設定 `unsubscribed_at` 並顯示退訂完成。重複退訂不會覆寫原本的時間。
- 退訂的訂閱者不在 `deliverable()` 範圍內，廣播與系列模式都不會再寄給他們。

### 廣播模式

指令 `newsletter:send-due`，每分鐘執行，使用 `withoutOverlapping(10)`，註冊於 `routes/console.php`。

1. 找出 `scheduled_at` 不為 `null`、已到期、且 `dispatched_at` 為 `null` 的期數。
2. 以條件式更新設定 `dispatched_at`，只有搶到更新的程序會繼續。
3. 取得 `NewsletterIssue::recipients()`，即同主題、`deliverable()`、且 `series_started_at` 為 `null` 的訂閱者，也就是 4.0.0 版以前就存在、沒有進入系列模式的人。
4. 為每位收件者建立 `SendNewsletterIssue` 並以 `Bus::batch()` 派送，全部結束後設定 `sent_at`。沒有收件者時直接設定 `sent_at`。
5. 派送過程拋出例外時，把 `dispatched_at` 還原為 `null` 再重新拋出，避免期數看似已派送、實際沒有進入佇列。

### 系列模式

指令 `newsletter:send-series`，每分鐘執行，使用 `withoutOverlapping(10)`，註冊於 `routes/console.php`。

對每位 `seriesDue()` 的訂閱者，即 `deliverable()`、`series_started_at` 不為 `null`、`series_next_at <= now()`，以 `lazyById()` 逐位處理：

1. 取得 `nextIssue()`，沒有下一封代表已追上進度，略過且不更新 `series_next_at`。
2. 以條件式更新搶佔：`WHERE id = ? AND series_next_at <= now()`，把 `series_next_at` 設為 `now()` 加上該主題的間隔天數。受影響筆數不是 1 就略過。
3. 派送 `SendNewsletterIssue`，帶入期數、訂閱者與該期目前的 `send_round`。

進度不另外儲存，由寄送紀錄推得：

- 已發布的期數：`scheduled_at` 不為 `null` 且 `scheduled_at <= now()`，不論廣播是否已派送。
- 下一封 `nextIssue()`：同主題已發布期數中，期號大於等於 `series_start_number`，且在該期目前的 `send_round` 下沒有這位訂閱者的寄送紀錄，取期號最小的一期。
- `seriesProgress()`：顯示「已收到 N / M 期」，廣播模式訂閱者顯示「廣播」。
- `nextIssueLabel()`：顯示「第 N 期」，已追上進度顯示「已追上」，廣播模式顯示「廣播」。

### 單封寄送

`App\Jobs\SendNewsletterIssue` 帶入期數、訂閱者與回合，最多嘗試 4 次，間隔 30、120、300 秒。寄出前依序檢查：

1. 批次已取消、期數或訂閱者已不存在，則結束。
2. 任務的回合與期數目前的 `send_round` 不同，代表期數已被改為未寄出、新回合有自己的任務，則結束。
3. 這個回合已有寄送紀錄，則結束，避免重試時重複寄出。
4. 訂閱者已不是 `deliverable()`，例如佇列期間退訂，則結束。

通過後以 `NewsletterIssueMail` 寄出，並寫入 `newsletter_deliveries`。信件內文為 Markdown，轉成 HTML 時移除原始 HTML 並禁止不安全的連結。

### 後台管理

所有資源都只有 `User::canAccessPanel()` 允許的帳號可以進入。

**電子報訂閱者** `/admin/newsletter-subscribers`

- 分頁籤：全部、尚未確認、已確認，即 `deliverable()`、已退訂，各自帶數量標示。
- 欄位：Email、電子報、狀態、確認時間、進度、下一封、訂閱時間、更新時間，後者預設隱藏。可搜尋 Email，可依電子報主題與確認狀態篩選。
- 新增訂閱者：與前台相同，寄出確認信，對方點擊後才算訂閱成功。
- 重寄確認信：只對尚未確認的訂閱者顯示。
- 直接確認訂閱：只對尚未確認的訂閱者顯示，也提供批次版本。呼叫 `confirm()`，同樣會進入系列模式。這是 4.0.0 版決定不做、之後改變的功能，用於管理員確認過對方身分的情境。
- 調整下一封：只對系列模式訂閱者顯示，下拉選單列出該主題所有期數，顯示為「第 N 期：標題」，包含尚未發布的期數，儲存時寫入 `series_start_number`。往後跳會略過所選期號之前還沒收到的期數，往前調只會讓期號落在所選值之後、尚未收到的期數被找出來，已收過的期數不會重寄。調整不改變 `series_next_at`。
- 刪除：單筆與批次。

**電子報期數** `/admin/newsletter-issues`

- 列表：電子報、期號、標題、預計寄送時間、狀態、已寄出人數，預設依期號排序，可依電子報主題篩選。狀態為未排程、已排程、派送中、已寄出。已寄出人數只計算目前回合。
- 預計寄送時間可以直接點擊儲存格以對話框編輯，時區為 `Asia/Taipei`，資料庫以 UTC 儲存，留空代表尚未排程。已派送的期數不可編輯。
- 新增與編輯：主題、期號，同主題不可重複、標題、摘要、Markdown 內文、預計寄送時間。已派送的期數所有欄位唯讀。
- 改為未寄出：對已派送，包含卡在派送中，的期數顯示。清空 `scheduled_at`、`dispatched_at`、`sent_at`，並讓 `send_round` 加 1，舊的寄送紀錄保留。重新排程後，所有廣播與系列模式的收件者會再收到一次這一期。
- 刪除：只能刪除尚未派送的期數。

**系列寄送間隔** `/admin/newsletter-topic-settings`

只有列表，沒有新增，每個主題一列，開啟頁面時 `syncWithTopics()` 補齊缺少的主題。欄位「每幾天寄一封」可直接在列表編輯，必填、整數、至少為 1。調整只影響之後的寄送，已排定的 `series_next_at` 不變。

### 匯入指令

`php artisan newsletter:import-issues {path?}` 從 Markdown 檔匯入 Filament 主題的期數，檔名格式為 `第 NN 期：標題.md`，預設目錄為 `docs/FilamentNewsletter`。檔案開頭的 front matter 可帶 `summary`，內文開頭的 `#` 標題會被移除。已派送的期數略過，其餘以 `updateOrCreate` 寫入。

### 部署

部署後執行：

```bash
php artisan migrate
```

- 排程器必須持續執行，`newsletter:send-due` 與 `newsletter:send-series` 都靠它每分鐘觸發。
- 必須有佇列 worker 處理 `SendNewsletterIssue` 與 `StoreNewsletterSubscriber`。
- 遷移只新增欄位與資料表，`series_start_number` 預設為 1，不改動既有資料，部署前就存在的訂閱者保持廣播模式。只有部署後才確認信箱的主題訂閱者進入系列模式。

### 已確定設計決策

1. **沿用 4.0.0 版決策 1 至 5。** 單一資料表、列舉主題、簽章連結、各主題各自確認、確認信走佇列。
2. **進度由寄送紀錄推得，不另存欄位。** 原因：寄送紀錄本來就是事實來源，另存進度會有兩份資料不一致的風險，「改為未寄出」後也能自然重寄。
3. **系列間隔以主題為單位，不提供每位訂閱者自訂。** 預設 7 天，管理員在後台設定。
4. **現有訂閱者維持廣播模式。** 避免部署當下對既有名單突然寄出大量舊期數。
5. **以條件式更新搶佔，取代鎖。** 廣播的 `dispatched_at` 與系列的 `series_next_at` 都用「只有更新成功的程序才派送」避免重複派送。
6. **單封任務自行檢查回合、重複紀錄與退訂。** 任務可以安全重試，也不怕期數在佇列期間被改為未寄出。
7. **退訂分成確認頁與實際寫入兩步。** 原因：信件掃描器會開啟連結，`GET` 不能產生副作用。

### 已知限制

- 4.0.0 版的限制仍成立：一般電子報的 `['email', 'topic']` 唯一索引對 `null` 無效，去重只靠 Job 內的 `firstOrCreate`。
- 廣播在到期後會把整批期數標記為已派送，即使該主題只有系列模式訂閱者，也會因沒有收件者而直接標記為已寄出，這會鎖住該期的編輯。要再編輯需先「改為未寄出」，但這會使 `send_round` 加 1，已收過的系列模式訂閱者會再收到一次，期數順序也可能跳動。
- 系列寄送在搶佔之後才派送任務，派送拋出例外時這一個間隔會被消耗，且該次指令中止，下一輪才會繼續。
- 後台訂閱者列表的進度與下一封欄位逐列查詢，名單很大時會有 N+1 查詢。已追上進度的訂閱者每分鐘都會被重新掃描。
- 間隔天數沒有上限驗證，輸入過大的值在 MySQL 或 PostgreSQL 會因欄位範圍而發生錯誤。
- 已退訂的系列模式訂閱者在後台仍會顯示下一封與調整下一封。

### 驗收條件

- [ ] 主題訂閱者點擊確認連結後，`series_started_at` 與 `series_next_at` 有值，第 1 期立即在下一輪排程寄出
- [ ] 一般電子報訂閱者確認後不進入系列模式
- [ ] 系列模式依主題間隔寄下一期，間隔未到不寄，草稿與未來才排程的期數不寄
- [ ] 已追上進度的訂閱者在新期數發布後，下一輪排程就會寄出，不必等滿一個間隔
- [ ] 同一位訂閱者同一期在同一回合不會收到兩封
- [ ] 系列模式訂閱者不出現在 `recipients()`，部署前就存在的訂閱者仍由 `newsletter:send-due` 寄出
- [ ] 信件內有退訂連結與 `List-Unsubscribe` 標頭，`GET` 退訂連結不寫入資料，`POST` 才設定 `unsubscribed_at`
- [ ] 簽章被竄改的退訂連結回傳 403，找不到訂閱者回傳 404
- [ ] 退訂後的訂閱者不再收到信，重新訂閱並確認後，從尚未收過的期數接續
- [ ] 已派送的期數內容唯讀，只能刪除尚未派送的期數
- [ ] 改為未寄出後 `send_round` 加 1，舊的寄送紀錄保留，重新排程後所有收件者再收到一次
- [ ] 預計寄送時間以台北時間輸入並以 UTC 儲存，可直接在列表頁編輯
- [ ] 後台調整下一封後，被略過的期數不再補寄，往前調時已收過的期數不會重寄
- [ ] 廣播模式訂閱者看不到調整下一封，下拉選單只列出該訂閱者主題下的期數
- [ ] 後台直接確認訂閱後，訂閱者即為已確認並進入系列模式
- [ ] 系列寄送間隔可在後台逐主題設定，未設定時為 7 天
