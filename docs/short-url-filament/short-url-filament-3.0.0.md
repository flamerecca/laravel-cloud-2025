## Short URL 後台規格書 3.0.0

### 概述

`Short URL 後台 3.0.0` 提供比起 [Short URL 後台 2.0.0](short-url-filament-2.0.0.md) 更進階的服務。

後台加上管理網址標籤（domain owners）的資訊。

### 功能規格

#### 網址標籤管理

* 建立網址標籤
    * 自訂網址標籤名稱
* 編輯網址標籤
    * 編輯名稱
    * 編輯標籤對應的網址
* 刪除網址標籤
    * 單筆刪除 / 批次刪除

#### Filament 介面需求

##### Domain Tag Resource

* **表單欄位**
    * 標籤名稱
    * 與標籤關聯的網址

##### Short URL Resource 調整

