## 短網址產生器 Livewire 頁面

### 專案目標

開發一個單一頁面，使用者可以輸入原始網址，點擊按鈕後即時產生對應的短網址，並顯示在畫面上。

### 功能需求

#### 頁面功能

使用者可以輸入一個原始網址（例如：https://www.example.com）

按下「產生短網址」按鈕後，畫面即時顯示產生的短網址

#### 短網址產生規則

使用隨機的 10 碼英數字組合做為短網址碼（slug）

需避免與資料庫中既有 slug 重複

最終顯示的短網址格式為：http://your-domain.test/s/{slug}

#### 資料庫設計

建立一張 `short_urls` 資料表，欄位如下：

| 欄位名稱         | 型別          | 說明      |
|--------------|-------------|---------|
| id           | bigint      | 主鍵，自動遞增 |
| original_url | text        | 原始網址    |
| slug         | string(255) | 短網址唯一碼  |
| created_at   | timestamp   | 建立時間    |
| updated_at   | timestamp   | 更新時間    |

### 開發需求

#### 建立 Livewire Component

| 項目   | 說明                               |
|------|----------------------------------|
| 名稱   | UrlShortener                     |
| 狀態變數 | originalUrl, shortUrl            |
| 方法   | generateShortUrl()               |
| 視圖檔案 | livewire/url-shortener.blade.php |

#### Blade 視圖功能

畫面上應包含以下元素：

- 一個輸入欄位讓使用者輸入網址
- 一個按鈕「產生短網址」
- 產生成功後顯示短網址並提供點擊連結

#### Blade 視圖範例

```html
<div class="max-w-xl mx-auto p-6 bg-white shadow rounded-lg space-y-4">
    <input type="text"
           class="w-full border rounded p-2"
           placeholder="請輸入原始網址..." />
    
    <button class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
        產生短網址
    </button>

    <!--@if ($shortUrl)-->
        <div class="mt-4">
            <label class="font-semibold">短網址：</label>
            <a href="http://shortUrl" target="_blank" class="text-blue-600 underline">{{ $shortUrl }}</a>
        </div>
</div>
```

#### 建立專屬頁面

| 項目       | 說明                                     |
|----------|----------------------------------------|
| 路由       | /shorten                               |
| Blade 檔案 | resources/views/shorten.blade.php      |
| 內容       | 嵌入單一 Livewire 元件 url-shortener，不包含多餘內容 |

### 範例畫面流程

- 使用者輸入 https://laravel.com 並按下按鈕
- 畫面顯示短網址如 http://your-domain.test/s/A1B2C3D4E5

使用者可點擊該短網址進行跳轉

### 驗收條件

- [ ] 頁面能正確顯示 Livewire 元件並執行功能
- [ ] 成功產生短網址並可從 /s/{slug} 正確跳轉
- [ ] 使用者無需頁面刷新
