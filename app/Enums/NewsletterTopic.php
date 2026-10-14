<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum NewsletterTopic: string implements HasLabel
{
    case Filament = 'filament';
    case Laravel = 'laravel';
    case SpringBoot = 'spring-boot';
    case Ktor = 'ktor';

    public function getLabel(): string
    {
        return $this->title();
    }

    /**
     * The path segment of the public subscription page. Stored values stay untouched so existing
     * subscribers and signed links keep working.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Filament => 'filament',
            self::Laravel => 'laravel-test',
            self::SpringBoot => 'spring-boot',
            self::Ktor => 'ktor',
        };
    }

    public static function fromSlug(string $slug): ?self
    {
        foreach (self::cases() as $topic) {
            if ($topic->slug() === $slug || $topic->value === $slug) {
                return $topic;
            }
        }

        return null;
    }

    /**
     * Path of a second logo shown next to the topic logo on the subscription page, if any.
     */
    public function secondaryLogo(): ?string
    {
        return match ($this) {
            self::Filament => null,
            self::Laravel => 'resources/images/pest.png',
            self::SpringBoot, self::Ktor => 'resources/images/Kotlin.png',
        };
    }

    public function secondaryLogoLabel(): ?string
    {
        return match ($this) {
            self::Filament => null,
            self::Laravel => 'Pest',
            self::SpringBoot, self::Ktor => 'Kotlin',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::Filament => 'Filament 進階分享',
            self::Laravel => 'Laravel 自動化測試分享',
            self::SpringBoot => 'Spring Boot 搭配 Kotlin 基礎開發',
            self::Ktor => 'Ktor 基礎教學',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Filament => '寫給已經會用 Filament 內建功能做出後台，卻被一個翻遍文件也找不到對應元件的需求卡住的 Laravel 工程師。這是一份三個月、共 12 期的電子報，每週一期，每封信獨立成篇、只講一件事，帶你從自訂欄位一路走到即時推播與套件封裝，並誠實告訴你哪些需求已經不該留在 Filament 裡。',
            self::SpringBoot => '寫給已經會寫 Kotlin 語法，第一次碰 Spring Boot，專案產生之後面對一堆檔案與標註，不知道哪些該先搞懂的後端工程師。這是一份三個月、共 12 期的電子報，每週一期，每封信獨立成篇、只講一件事，帶你從 Initializr 產生的專案一路走到能驗證、能測試的第一支服務。',
            self::Ktor => '寫給有 Laravel、Spring Boot 或 Node.js 後端經驗，決定動手試 Ktor 的工程師。選型文回答「要不要試」，這份三個月、共 12 期的電子報接著回答「決定試之後，第一個月會撞到什麼」。每週一期，每封信獨立成篇、只講一件事，帶你從 main() 一路走到 Docker 映像檔，並誠實列出系列沒有涵蓋的方向。',
            self::Laravel => '寫給已經熟悉 Laravel 基本用法，想用自動化測試守住專案品質、改程式時不再提心吊膽的開發者。每封電子報聚焦一個測試主題，附上可直接套用的 Pest 實戰範例。',
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
            self::SpringBoot => 'resources/images/spring-boot.png',
            self::Ktor => 'resources/images/ktor.png',
        };
    }

    /**
     * The key points shown in a block of their own on the subscription page, if the topic has any.
     *
     * @return array<int, array{title: string, description: string}>
     */
    public function keyPoints(): array
    {
        return match ($this) {
            self::Filament => [
                ['title' => '每週一期，獨立成篇', 'description' => '每封信只講一件事，不需要追過前面幾期，任何一週加入都看得懂'],
                ['title' => '一杯咖啡的時間讀完', 'description' => '每期約 800 到 1500 字，專注解決一個具體的卡關情境'],
                ['title' => '誠實說出框架的邊界', 'description' => '從自訂欄位走到即時推播，也會告訴你哪些需求不該勉強留在 Filament 裡'],
            ],
            self::Ktor => [
                ['title' => '每週一期，獨立成篇', 'description' => '每封信只講一件事，不需要追過前面幾期，任何一週加入都看得懂'],
                ['title' => '從 main() 走到 Docker', 'description' => '全系列沿用同一個訂單查詢服務，一條路線串起路由、序列化、資料庫、認證、測試與部署'],
                ['title' => '替有後端經驗的你省時間', 'description' => '拿 Laravel、Spring Boot 的概念對照過來，直接說明 Ktor 少了什麼、要自己補什麼'],
            ],
            self::SpringBoot => [
                ['title' => '每週一期，獨立成篇', 'description' => '每封信只講一件事，不需要追過前面幾期，任何一週加入都看得懂'],
                ['title' => '一條完整的學習路線', 'description' => '全系列沿用同一個片段庫服務，從專案產生、端點、資料庫一路走到驗證與測試'],
                ['title' => '先說清楚卡關的原因', 'description' => '每期從一個你在動手時會撞到的具體問題出發，並誠實列出系列沒有涵蓋的方向'],
            ],
            self::Laravel => [
                ['title' => '從第一個測試開始', 'description' => '不需要測試經驗，用 Pest 從最簡單的斷言一步步建立信心'],
                ['title' => '貼近真實專案', 'description' => '範例來自實際的 Laravel、Livewire 與 Filament 專案，不是玩具程式'],
                ['title' => '改程式不再提心吊膽', 'description' => '學會決定該測什麼，讓重構與升級版本時有測試替你把關'],
            ],
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
            self::Ktor => [
                ['title' => '十行程式碼跑起一個服務，然後看它缺了什麼', 'description' => '看懂最小專案的每一行，與其他框架少了哪些預設'],
                ['title' => '路由寫在哪裡，路徑參數拿不到怎麼辦', 'description' => '路由分組、路徑與查詢參數的取法'],
                ['title' => 'JSON 進來出去，欄位對不上時發生什麼事', 'description' => 'ContentNegotiation 與序列化失敗的行為'],
                ['title' => 'install() 一下，你的請求就多了一層', 'description' => 'Plugin 機制與請求管線，自己寫一個'],
                ['title' => '例外丟出去之後，使用者看到的是什麼', 'description' => 'StatusPages 與統一錯誤格式'],
                ['title' => 'handler 裡的 suspend，到底讓出了什麼', 'description' => '協程在 handler 裡的行為與阻塞陷阱'],
                ['title' => '資料庫連線，在協程裡該怎麼開', 'description' => 'Exposed、連線池與協程交易'],
                ['title' => '物件越來越多，誰來負責 new', 'description' => 'Koin 與手動組裝的取捨'],
                ['title' => '登入之後，每個請求怎麼證明自己是誰', 'description' => 'JWT 認證與路由保護'],
                ['title' => '測試不用真的開伺服器', 'description' => 'testApplication 與替換依賴'],
                ['title' => '同一份程式，怎麼在三個環境跑起來', 'description' => '組態設定、環境變數、fat jar 與 Docker'],
                ['title' => '十二週後，拿一份清單去評估你的第一支服務', 'description' => '把前 11 期收斂成上線前問題清單'],
            ],
            self::SpringBoot => [
                ['title' => '剛產生的專案，哪些檔案可以先不用管', 'description' => '看懂 build.gradle.kts、設定檔與進入點三樣就夠'],
                ['title' => '為什麼 Kotlin 類別在 Spring 裡突然要加 open', 'description' => 'kotlin-spring 外掛替你處理了什麼'],
                ['title' => '誰來負責 new', 'description' => '建構子注入，與三個角色標註的差別'],
                ['title' => '五個端點怎麼寫，參數各用哪個註解拿', 'description' => '路徑參數、查詢參數、請求主體的取法'],
                ['title' => '全部回 200 的 API，前端怎麼知道出事了', 'description' => 'ResponseEntity 與狀態碼的選擇'],
                ['title' => 'Entity 需要無參數建構子，Kotlin 類別怎麼辦', 'description' => 'kotlin-jpa 外掛與 Entity 建模'],
                ['title' => '不寫 SQL，findByLanguage 為什麼就能查', 'description' => 'Repository 與衍生查詢方法的命名慣例'],
                ['title' => '把記憶體換成資料庫，API 一行都不用改', 'description' => '整合 Service 與 Repository，H2 的定位'],
                ['title' => '標題留空，API 應該回什麼', 'description' => 'Bean Validation 與 @Valid'],
                ['title' => '三支 API 三種錯誤格式', 'description' => '@RestControllerAdvice 統一錯誤回應'],
                ['title' => '不用每次改完都手動點 Postman', 'description' => '@SpringBootTest 與 MockMvc 的最小測試'],
                ['title' => '十二週後，拿一份清單檢查你的第一支服務', 'description' => '收斂成問題清單，並列出系列沒有涵蓋的方向'],
            ],
            self::Laravel => [
                ['title' => 'Pest 入門與測試思維', 'description' => '從第一個測試開始，建立「先寫測試」的開發節奏'],
                ['title' => 'Feature 測試實戰', 'description' => '模擬請求、認證與表單確認，驗證完整的使用流程'],
                ['title' => '資料庫與 Factory', 'description' => '用 Factory 與資料庫交易，讓每個測試彼此獨立'],
                ['title' => 'Fake 與 Mock', 'description' => '替換郵件、佇列與外部服務，避免測試碰到真實環境'],
                ['title' => '測試 Livewire 與 Filament', 'description' => '針對互動元件與後台資源撰寫穩定的測試'],
                ['title' => 'CI 與測試策略', 'description' => '在持續整合中自動執行測試，並決定該測什麼、不該測什麼'],
            ],
        };
    }
}
