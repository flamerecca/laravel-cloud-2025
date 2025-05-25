## Newsletter 系統

提供使用者輸入 email 訂閱 Newsletter。

初期使用者不多，所以只需要紀錄哪些人需要寄信即可，實際寄信後續補上。

### 技術架構
- Laravel Livewire
- Laravel Mail
- Eloquent ORM
- PostgreSQL

資料表：subscribers

| 欄位         | 類型        | 說明            |
|------------|-----------|---------------|
| id         | bigint    | PK            |
| email      | string    | 訂閱者 email（唯一） |
| created_at | timestamp | 建立時間          |
| updated_at | timestamp | 更新時間          |

### 畫面需求
- Email 輸入欄位 
- 「訂閱」按鈕
- 訂閱成功訊息
- 訂閱錯誤處理（Email 重複、Email 格式錯誤）

### 驗收條件

- [ ] 頁面能正確顯示 Livewire 元件並執行功能
- [ ] 成功紀錄訂閱者 email
- [ ] 使用者無需頁面刷新
- [ ] 輸入錯誤 email 會提示錯誤訊息
- [ ] 輸入重複 email 會提示錯誤訊息
