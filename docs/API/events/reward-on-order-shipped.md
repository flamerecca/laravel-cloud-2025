### 功能規格書：訂單出貨獎勵系統

#### 簡介

本文件旨在規格化「訂單出貨後，自動獎勵顧客」此一功能的實作細節。當系統將一筆訂單的狀態更新為「已出貨」時，應觸發一個事件，並由一個監聽器來處理後續的獎勵發放邏輯，例如贈送優惠券或禮品卡。

此設計旨在將「更新訂單狀態」的核心業務邏輯與「發放獎勵」的附加邏輯解耦，提高程式碼的可維護性與擴展性。

#### 功能名稱

訂單出貨後獎勵禮品卡 (Reward Gift Card on Order Shipped)

#### 情境說明

- **參與者:** 系統 (後台管理員或自動化腳本)
- **前置條件:**
    1. 系統中存在一筆屬於某位顧客 (User) 的訂單 (Order)。
    2. 該訂單尚未出貨。
- **觸發條件:** 系統將該訂單的狀態更新為「已出貨 (shipped)」。
- **預期結果:**
    1. 系統觸發一個 `OrderShipped` 事件。
    2. 一個 `RewardGiftCard` 監聽器接收到此事件。
    3. 監聽器為該訂單的顧客生成一張新的禮品卡 (Gift Card)。
    4. (可選) 系統發送通知給顧客，告知其已獲得禮品卡。

#### 4. 技術元件

##### 4.1. 事件 (Event)

- **類別:** `App\Events\OrderShipped`
- **目的:** 代表「訂單已出貨」這個時間點發生的事件。
- **資料結構:** 事件物件應包含觸發此事件的訂單實例 (`Order` model)，以便監聽器可以存取訂單相關資訊。

##### 4.2. 監聽器 (Listener)

- **類別:** `App\Listeners\RewardGiftCard`
- **目的:** 監聽 `OrderShipped` 事件，並執行發放禮品卡的邏輯。
- **相依性:** 可能需要注入一個服務 (e.g., `GiftCardService`) 來處理禮品卡的生成。
- **處理邏輯 (`handle` method):**
    1. 從事件物件中取得 `Order` 實例 (`$event->order`)。
    2. 取得與訂單關聯的顧客 (`$event->order->customer`)。
    3. 執行業務邏輯，為該顧客創建一張禮品卡。

##### 4.3. 事件與監聽器註冊

- **位置:** `app/Providers/EventServiceProvider.php`
- **目的:** 將 `OrderShipped` 事件與 `RewardGiftCard` 監聽器綁定。

##### 4.4. 事件觸發點

- **位置:** 任何更新訂單狀態為「已出貨」的地方，例如 `OrderController` 或 `OrderService`。
- **觸發方式:** 使用 `dispatch` 輔助函數或 `Event` Facade。

#### 驗收標準與測試案例

為了確保功能正常，應撰寫以下測試：

1.  **單元測試 (`RewardGiftCard` Listener):**
    -   **目的:** 驗證監聽器在給定事件時，能正確呼叫 `GiftCardService`。
    -   **方法:** Mock `GiftCardService`，觸發監聽器的 `handle` 方法，並斷言 `createForCustomer` 方法被正確呼叫。

2.  **功能測試 (Event Dispatching):**
    -   **目的:** 驗證當訂單出貨的 API 端點被呼叫時，`OrderShipped` 事件會被觸發，且 `RewardGiftCard` 監聽器會被推送到隊列中。
    -   **方法:**
        -   使用 `Event::fake()` 來攔截事件。
        -   呼叫更新訂單狀態的路由 (e.g., `POST /orders/{order}/ship`)。
        -   使用 `Event::assertDispatched(OrderShipped::class)` 斷言事件已被觸發。
        -   使用 `Queue::fake()` 和 `Queue::assertPushed()` 斷言監聽器已被推送到隊列。
