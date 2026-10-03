> **已被取代**：本版的「不儲存訂閱名單、全權交由 n8n Webhook 轉發」設計已被取代，正式規格請見 [docs/Newsletter/newsletter-subscribe-2.0.0.md](newsletter-subscribe-2.0.0.md)。本檔案保留供歷史對照。

## 訂閱電子報頁面 (Newsletter Subscription Page)

### 概述

首頁同時作為「訂閱電子報」的著陸頁使用，訪客不需登入即可以 Email 訂閱電子報。此頁面目前已有初版實作，本規格以現況實作為基礎，重新設計成正式版本，取代 [docs/Livewire/newsletter/news-letter-1.0.0.md](../Livewire/newsletter/news-letter-1.0.0.md) 原先規劃的 Livewire 元件 + `subscribers` 資料表設計。

> 差異說明：舊版規格假設會用 Laravel Livewire 打造即時互動表單，並在 PostgreSQL 建立 `subscribers` 資料表自行保存訂閱者名單與去重。實際落地時，訂閱名單與後續寄送排程改交由 n8n Workflow 統一管理，Laravel 端只負責表單頁面與驗證，不再自行儲存訂閱者資料，因此不再需要 Livewire 或 `subscribers` 資料表。此設計差異以下方「已確定設計決策」第 1 項記錄。

### 頁面位置

- 路由：`GET /`，路由名稱 `home`，定義於 `routes/web/web.php`
- View：`resources/views/welcome.blade.php`
- 首頁同時扮演「品牌著陸頁」與「電子報訂閱表單」兩種角色；若日後首頁需要承載電子報以外的內容，此表單應優先遷出至獨立路由，目前維持與首頁共用一個頁面

### 技術架構

- Blade 原生表單，非 Livewire：訂閱表單只需要「送出 → 頁面重導向 → Session Flash 訊息」的同步流程，不需要即時互動，維持頁面輕量
- 提交路由改掛在 `web` Middleware Group 下，路徑為 `POST /newsletter`：
  - 路由檔由 `routes/api/newsletter.php` 移至 `routes/web/newsletter.php`，並在 `bootstrap/app.php` 的 `web:` 陣列註冊，從 `api:` 陣列移除
  - 網址由現況的 `/api/newsletter` 改為 `/newsletter`
- 驗證邏輯抽成 Form Request 類別 `App\Http\Requests\SubscribeNewsletterRequest`，附繁體中文自訂錯誤訊息，取代 Controller 內目前的 inline `$request->validate()`
- Controller 不再於 Request 生命週期內同步呼叫外部 Webhook，改為 dispatch 佇列任務 `App\Jobs\ForwardNewsletterSubscription`（`ShouldQueue`），由佇列非同步呼叫 n8n Webhook，並設定重試次數與退避時間
- 提交端點加上 `throttle:6,1` 限流，比照專案內既有 `routes/web/auth.php` 中 `verify-email` 端點的限流慣例

### 資料表

- 不在 Laravel 端建立 `subscribers` 資料表，訂閱名單、去重與後續寄送排程完全交由 n8n Workflow 負責
- 原因：初期用量小，若 Laravel 與 n8n 兩邊各自維護一份名單，容易造成資料不同步；Email 重複判斷、退訂管理等機制統一交給 n8n 端處理

### 畫面需求

依現有 `resources/views/welcome.blade.php` 版型：

- 頁面標題「訂閱電子報」
- Email 輸入欄位，`type="email"`，`placeholder="請輸入您的 Email"`，必填，驗證失敗時透過 `old('email')` 保留原輸入值
- 「訂閱」送出按鈕
- 送出成功：頁面上方顯示綠色成功提示，文案「感謝訂閱！請到信箱查看確認信」
- 送出失敗：Email 必填或格式錯誤時，頁面上方顯示紅色錯誤提示，文案改為繁體中文自訂訊息（見 `SubscribeNewsletterRequest`）
- 右側維持現有粉紅色底 + 電子報 icon 裝飾區塊，不在本次規格調整範圍內
- 頁首登入／註冊導覽列沿用現有版型的 `@auth` 判斷，不受此表單影響

### 提交流程

1. 使用者於首頁輸入 Email，點擊「訂閱」
2. 表單以 `POST /newsletter` 送出，帶 CSRF Token
3. `SubscribeNewsletterRequest` 驗證 Email 必填且格式正確
4. 驗證失敗：導回首頁並帶 `$errors`，頁面顯示紅色錯誤訊息
5. 驗證成功：Controller dispatch `ForwardNewsletterSubscription` Job，帶入 `email` 與 `subscribed_at`
6. Controller 立即以 Session Flash `success` 訊息重導回首頁，不等待 Webhook 實際送達完成
7. 佇列 Worker 非同步執行 Job，呼叫 n8n Webhook；失敗時依 Job 的 `tries`／退避時間設定自動重試，多次失敗後落入 `failed_jobs` 資料表，供後續人工查看

### 已確定設計決策

1. **不在 Laravel 端保存訂閱名單，全權交由 n8n 管理。** 原因：避免 Laravel 與 n8n 雙邊重複維護名單，初期規模也不需要本地報表功能。此決策取代 [docs/Livewire/newsletter/news-letter-1.0.0.md](../Livewire/newsletter/news-letter-1.0.0.md) 原本規劃的 Livewire + `subscribers` 資料表設計。
2. **提交端點改為 `web` Middleware Group、路徑改為 `/newsletter`。** 原因：現況掛在 `api` Middleware Group 下（實際網址為 `/api/newsletter`），沒有 Session／CSRF 防護，Controller 內的 `back()` 重導向行為在無 Session 的情況下並不可靠。
3. **外部 Webhook 呼叫改為佇列非同步處理。** 原因：現況在 Controller 內同步呼叫 `Http::post()`，若 n8n 服務延遲或中斷，會直接拖慢甚至中斷使用者的請求；改為佇列任務後可取得自動重試能力，且不影響使用者的即時回饋。
4. **驗證邏輯抽成 Form Request，並附繁體中文自訂錯誤訊息。** 原因：遵循專案既有的 Controllers & Validation 慣例，避免顯示 Laravel 預設的英文驗證訊息。
5. **提交端點加上 `throttle:6,1` 限流。** 原因：比照專案內 `verify-email` 端點既有慣例，避免表單被程式化重複送出灌爆 n8n Webhook。

### 待確認事項

- 是否需要提示「這個 Email 已經訂閱過」？目前設計刻意不做重複判斷，一方面 Laravel 端無名單可查，另一方面也可避免透過錯誤訊息洩漏某 Email 是否已在名單內；若未來需要此提示，需先確認 n8n 能否同步回傳判斷結果給 Laravel。
- 是否需要提供退訂連結或退訂頁面？目前規格不包含退訂流程。

### 驗收條件

- [ ] 首頁可看到「訂閱電子報」表單，含 Email 欄位與「訂閱」按鈕
- [ ] 送出有效 Email 後，頁面顯示「感謝訂閱！請到信箱查看確認信」成功訊息
- [ ] 送出空白或格式錯誤的 Email，頁面顯示繁體中文錯誤訊息，且原輸入值透過 `old('email')` 保留
- [ ] 提交路由為 `POST /newsletter`，且受 CSRF 保護
- [ ] 呼叫 n8n Webhook 的行為透過佇列任務執行，可用 `Queue::fake()` 驗證有 dispatch `ForwardNewsletterSubscription`
- [ ] 短時間內重複送出超過限流門檻會收到 429 回應
