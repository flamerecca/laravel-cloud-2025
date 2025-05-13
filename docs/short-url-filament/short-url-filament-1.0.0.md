## Short URL 後台規格書 1.0.0

### 概述

`Short URL 後台 1.0.0` 可使用畫面管理短網址（Short Url）的內容


### Tech Stack

* Laravel 12
* Filament v3 (Admin Panel)
* PHP 8.4

### 功能規格

#### 短網址管理

* 建立短網址
    * 輸入原始 URL（自動驗證格式）
    * 自訂短碼（可選，系統會驗證唯一性）
* 編輯短網址
    * 編輯原始 URL
    * 編輯短碼
* 刪除短網址
    * 單筆刪除 / 批次刪除

#### Filament 介面需求

##### Short URL Resource

* **表單欄位**
    * 原始 URL (required)
    * 自訂短碼 (optional)
    * 是否啟用 (toggle)
    * 開始時間 (datetimepicker)
    * 到期時間 (datetimepicker)
