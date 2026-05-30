# 公告系統介面設計 (Announcement View)

本文件說明公告系統的前端呈現方式，包含公告列表與公告詳細內容。

## 技術棧
- **Livewire Volt**: 用於元件邏輯。
- **Tailwind CSS**: 處理版面配置、卡片樣式、字體及顏色。

---

## 1. 公告列表頁面 (List View)

列表頁面應呈現所有已發佈且啟用的公告，並優先顯示置頂公告。

### 介面設計要點
- **置頂標記**: 使用紅色背景的標籤標示 `is_pinned` 為 true 的公告。
- **發佈時間**: 顯示格式化的發佈日期。
- **摘要**: 顯示內容的前兩行或限制字數。

### 建議的 Volt 元件結構
```php
use App\Models\Announcement;
use function Livewire\Volt\{state, computed};

$announcements = computed(fn() => Announcement::where('is_active', true)
    ->where('published_at', '<=', now())
    ->orderBy('is_pinned', 'desc')
    ->orderBy('published_at', 'desc')
    ->get());
?>

<div class="space-y-4">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">最新公告</h1>

    @if ($this->announcements->isEmpty())
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 text-center text-gray-500">
            目前尚無公告
        </div>
    @else
        @foreach ($this->announcements as $announcement)
            <div wire:key="ann-{{ $announcement->id }}" class="bg-white dark:bg-gray-800 shadow rounded-lg p-6 flex flex-col gap-2">
                <div class="flex justify-between items-start">
                    <div class="flex items-center gap-2">
                        @if ($announcement->is_pinned)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                置頂
                            </span>
                        @endif
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $announcement->title }}
                        </h3>
                    </div>
                    <span class="text-sm text-gray-500">
                        {{ $announcement->published_at?->format('Y-m-d') }}
                    </span>
                </div>
                
                <p class="text-gray-600 dark:text-gray-400 line-clamp-2">
                    {{ str($announcement->content)->limit(150) }}
                </p>

                <div class="mt-2">
                    <a href="{{ route('announcements.show', $announcement) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        閱讀全文 &rarr;
                    </a>
                </div>
            </div>
        @endforeach
    @endif
</div>
```

---

## 2. 公告詳細頁面 (Detail View)

顯示完整的公告標題、內容與發佈資訊。

### 介面設計要點
- **標題**: 使用醒目的標題樣式。
- **內容**: 保留換行符號。
- **返回按鈕**: 提供返回列表的導覽連結。

### 建議的 Volt 元件結構
```php
use App\Models\Announcement;
use function Livewire\Volt\{state};

state(['announcement' => fn (Announcement $announcement) => $announcement]);
?>

<div class="max-w-2xl mx-auto space-y-6">
    <a href="{{ route('announcements.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
        &larr; 返回列表
    </a>

    <div class="space-y-2">
        <div class="flex items-center gap-2">
            @if ($announcement->is_pinned)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                    置頂公告
                </span>
            @endif
            <span class="text-sm text-gray-500">
                發佈於 {{ $announcement->published_at?->format('Y-m-d H:i') }}
            </span>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white">{{ $announcement->title }}</h1>
    </div>

    <hr class="border-gray-200 dark:border-gray-700" />

    <div class="text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap">
        {{ $announcement->content }}
    </div>
</div>
```
