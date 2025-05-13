## Short URL 後台規格書 2.0.0

### 概述

`Short URL 後台 2.0.0` 提供比起 [Short URL 後台 1.0.0](short-url-filament-1.0.0.md) 更進階的服務。

後台加上管理網址擁有者（domain
owners）的資訊。

### 功能規格

#### 網址擁有者管理

* 建立網址擁有者
    * 輸入網址擁有者名稱
    * 輸入擁有者聯絡 Email
    * 輸入網址擁有者公司名稱（可選）
* 編輯網址擁有者
    * 編輯網址擁有者名稱
    * 編輯擁有者聯絡 Email
    * 編輯網址擁有者公司名稱
* 編輯網址擁有者所對應的網址
    * 新增擁有網址
    * 編輯擁有網址
    * 刪除擁有網址
* 刪除網址擁有者
    * 單筆刪除 / 批次刪除

#### Filament 介面需求

##### Domain Owner Resource

* **表單欄位**
    * 網址擁有者名稱 (required)
    * 擁有者聯絡 Email (required)
    * 網址擁有者公司名稱 (optional)
