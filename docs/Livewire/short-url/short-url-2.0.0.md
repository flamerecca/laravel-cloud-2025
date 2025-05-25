## 短網址產生器 Livewire 頁面 2.0.0

### 專案目標

若使用者輸入的網址已經存在於資料庫，則直接回傳原有的短網址

若輸入的網址格式錯誤，需提示錯誤訊息

#### Blade 視圖範例

加上錯誤訊息資訊

```html
<div class="max-w-xl mx-auto p-6 bg-white shadow rounded-lg space-y-4">
    <input type="text"
           class="w-full border rounded p-2"
           placeholder="請輸入原始網址..." />

    <!--@error('originalUrl')-->
    <div class="text-red-500 text-sm">{{ $message }}</div>
    
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

### 驗收條件

- [ ] 若使用者輸入的網址已經存在於資料庫，則直接回傳原有的短網址
- [ ] 輸入錯誤網址會提示錯誤訊息
