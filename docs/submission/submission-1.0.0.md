## 上傳作業 API 規格文件 1.0.0

### 概述

本 API 提供學生上傳作業 PDF 檔案的功能，並檢查檔案格式是否符合規範。
系統僅接受 PDF 格式檔案，確保作業檔案一致性。

### Base URL

```
POST /api/submissions
```

### Request Headers

| 名稱           | 值                    |
|--------------|----------------------|
| Content-Type | multipart/form-data	 |

### Request Body 範例

| 參數             | 型別     | 必填 | 說明           |
|----------------|--------|----|--------------|
| student\_id    | string | 是  | 學生識別碼        |
| assignment\_id | string | 是  | 作業識別碼        |
| file           | file   | 是  | 上傳的 PDF 作業檔案 |

### 驗證規則 (Validation)

* 檔案格式
    * file 參數的檔案必須符合：
        * MIME type = application/pdf
        * 副檔名 = .pdf
    * 檔案大小
        * 檔案大小不得超過 5MB
* 參數完整性
    * student_id、assignment_id、file 皆為必填，缺一不可

### 回應 (Response)

#### 成功 (200 OK)

| 欄位             | 型別     | 說明           |
|----------------|--------|--------------|
| message        | string | 成功訊息         |
| submission\_id | string | 系統產生的作業提交 ID |
| student\_id    | string | 是            | 學生識別碼        |
| assignment\_id | string | 是            | 作業識別碼        |
| filename       | file   | 是            | 上傳的 PDF 作業檔案名稱 |

#### 失敗

| 狀態碼                      | 條件                   | 回應格式 |
|--------------------------|----------------------|------|
| 422 Unprocessable Entity | 缺少必要參數、檔案格式錯誤或大小超過限制 | JSON |

##### 失敗範例

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "file": [
            "The file must be a file of type: pdf."
        ]
    }
}
```

#### 備註
- 本階段不處理檔案覆蓋、學生身分驗證或資料庫管理，僅聚焦「單次檔案上傳與驗證」
