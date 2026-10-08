<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NewsletterTopic: string implements HasLabel
{
    case Filament = 'filament';

    public function getLabel(): string
    {
        return $this->title();
    }

    public function title(): string
    {
        return match ($this) {
            self::Filament => 'Filament 進階分享',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Filament => '寫給已經會用 Filament 建立基本 CRUD，想把後台做得更好維護、更好用的 Laravel 開發者。每封電子報聚焦一個實戰主題，附上可直接套用的程式碼片段。',
        };
    }

    /**
     * @return array<int, array{title: string, description: string}>
     */
    public function highlights(): array
    {
        return match ($this) {
            self::Filament => [
                ['title' => 'Schema 表單進階', 'description' => '條件顯示、Repeater 巢狀結構與自訂表單元件的拆分方式'],
                ['title' => 'Table Builder 實戰', 'description' => '大量資料的查詢優化、自訂 Filter 與 Bulk Action 設計'],
                ['title' => 'Actions 與 Modal', 'description' => '把複雜業務流程包成可重用的 Action，並處理權限與確認'],
                ['title' => 'Widgets 與儀表板', 'description' => '統計圖表、即時更新與避免 N+1 查詢的資料準備技巧'],
                ['title' => '測試 Filament', 'description' => '用 Pest 與 Livewire 測試 Resource、Table 與 Action'],
                ['title' => 'v3 升級至 v4', 'description' => '升級時最常踩到的破壞性變更與逐步遷移策略'],
            ],
        };
    }
}
