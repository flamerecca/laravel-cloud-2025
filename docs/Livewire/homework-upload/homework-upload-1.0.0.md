## 上傳作業系統

讓學生可以建立一個功能模組，提供使用者上傳作業，系統會儲存檔案並顯示上傳紀錄。

### 頁面需求

#### 上傳作業頁 `/assignments/upload`

* 顯示一個表單欄位：

    * `姓名`（text，必填）
    * `Email`（email，必填）
    * `作業檔案`（file，限制為 PDF，最大 5MB）
* 按鈕：`上傳`
* 上傳成功後清空表單並顯示 `上傳成功` 訊息

只允許上傳 pdf 檔案

### 資料表結構 `assignments`

| 欄位名稱            | 類型        | 描述         |
|-----------------|-----------|------------|
| `id`            | bigint    | 主鍵         |
| `name`          | string    | 上傳者姓名      |
| `email`         | string    | 上傳者 Email  |
| `file_path`     | string    | 儲存檔案路徑     |
| `original_name` | string    | 使用者上傳的原始檔名 |
| `created_at`    | timestamp | 上傳時間       |
| `updated_at`    | timestamp | 更新時間       |

### 驗證規則

| 欄位    | 驗證條件                                         |
|-------|----------------------------------------------|
| name  | required, string, max:50                     |
| email | required, email, max:100                     |
| file  | required, file, mimetypes\:pdf, max:5120（KB） |

### 檔案儲存

* 使用 Laravel 的預設 `storage/app` 資料夾儲存
* 子路徑為 `assignments/`
* 儲存檔名使用隨機 UUID + `.pdf`
* 使用 `Storage::putFileAs()` 或 `storeAs()` 實作

### 實作建議結構

#### Livewire Component：`UploadAssignment.php`

* 屬性：`name`, `email`, `file`
* 方法：`submit()`

#### Blade Template：`upload-assignment.blade.php`

* 使用 `<form wire:submit.prevent="submit">` 包裹表單
* 錯誤訊息顯示 `<x-input-error />` 或手動處理
* 使用 Livewire `wire:model` 綁定欄位
