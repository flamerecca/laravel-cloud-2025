<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NewsletterTopic: string implements HasLabel
{
    case Filament = 'filament';
    case Laravel = 'laravel';

    public function getLabel(): string
    {
        return $this->title();
    }

    public function title(): string
    {
        return match ($this) {
            self::Filament => 'Filament 進階分享',
            self::Laravel => 'Laravel 進階分享',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Filament => '寫給已經會用 Filament 內建功能做出後台，卻被一個翻遍文件也找不到對應元件的需求卡住的 Laravel 工程師。這是一份三個月、共 12 期的電子報，每週一期，每封信獨立成篇、只講一件事，帶你從自訂欄位一路走到即時推播與套件封裝，並誠實告訴你哪些需求已經不該留在 Filament 裡。',
            self::Laravel => '寫給已經熟悉 Laravel 基本用法，想把專案寫得更穩健、更好測試與維護的開發者。每封電子報聚焦一個進階主題，附上可直接套用的實戰範例。',
        };
    }

    /**
     * Path of the topic logo, relative to the project root, for use with Vite::asset().
     */
    public function logo(): string
    {
        return match ($this) {
            self::Filament => 'resources/images/filament.png',
            self::Laravel => 'resources/images/Laravel.png',
        };
    }

    /**
     * @return array<int, array{title: string, description: string}>
     */
    public function highlights(): array
    {
        return match ($this) {
            self::Filament => [
                ['title' => '判斷客製化的層級', 'description' => '把「客製化程度」拆成可判斷的層級，不再每次卡關都從零開始猜'],
                ['title' => '自訂欄位', 'description' => '繼承 Filament 欄位基底類別，寫一個真正屬於自己的欄位'],
                ['title' => '自訂表格欄位呈現', 'description' => '唯讀版本的客製化，與自訂欄位是同一套邏輯的另一半'],
                ['title' => '不只四個畫面的 Resource', 'description' => '打破列表、建立、編輯、檢視這四頁的預設假設'],
                ['title' => '品牌化的三種層級', 'description' => '視覺客製化之前，先分清楚自己站在哪一層'],
                ['title' => '跳出 Schema', 'description' => '把完全由自己掌控的 Livewire 元件，請進 Panel 頁面'],
                ['title' => '元件狀態與生命週期', 'description' => '狀態消失、邏輯重複觸發，多半是邏輯放錯了時機'],
                ['title' => '拖拉互動實作', 'description' => '把骨架變成使用者真的能操作的功能，並處理前端函式庫的狀態同步'],
                ['title' => '即時推播', 'description' => '用 Laravel 廣播機制，讓多位使用者同時看到資料的即時變化'],
                ['title' => '封裝成套件', 'description' => '先判斷功能夠不夠獨立，再收斂成可以安裝與重複使用的套件'],
                ['title' => '客製化的效能代價', 'description' => '疊加客製化之後，如何衡量效能成本與功能價值'],
                ['title' => '你自己的判斷清單', 'description' => '收斂成下次卡關時能直接拿來用的問題清單'],
            ],
            self::Laravel => [
                ['title' => 'Eloquent 進階查詢', 'description' => '子查詢、自訂 Cast 與避免 N+1 查詢的實戰技巧'],
                ['title' => '服務容器與依賴注入', 'description' => '綁定、解析與介面抽換，讓程式碼更容易測試'],
                ['title' => '佇列與排程', 'description' => 'Job 設計、失敗重試與排程任務的穩定性'],
                ['title' => '測試策略', 'description' => '用 Pest 撰寫 Feature 測試，搭配 Fake 與資料庫交易'],
                ['title' => '快取與效能', 'description' => '快取策略、查詢優化與效能瓶頸診斷'],
                ['title' => '部署與升級', 'description' => 'Laravel Cloud 部署流程與版本升級要點'],
            ],
        };
    }
}
