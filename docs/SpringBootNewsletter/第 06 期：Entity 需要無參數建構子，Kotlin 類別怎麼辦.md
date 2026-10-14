---
category: Spring Boot 基礎教學
status: draft
summary: JPA 要求 Entity 有無參數建構子，Kotlin 主建構子卻沒有提供。kotlin-jpa 外掛替你補上，並看一下資料類別當 Entity 的取捨。
tags:
  - Spring Boot
  - Kotlin
  - JPA
  - kotlin-jpa
---

# Entity 需要無參數建構子，Kotlin 類別怎麼辦

你照著課程把 `Snippet` 宣告成 Entity，啟動時 JPA 丟出一個錯誤：找不到這個 Entity 的預設建構子。

這是 Kotlin 搭配 JPA 時，最多人卡住的一個地方。錯誤訊息看起來像是你的類別寫錯了，其實類別本身沒有問題。

## 為什麼找不到

JPA 從資料庫讀出資料時，需要先建立一個空物件，再把欄位逐一填進去，所以要求 Entity 有一個無參數建構子。

Kotlin 的類別通常把所有欄位寫在主建構子裡，沒有無參數的版本。兩邊的假設衝突，於是出錯。錯誤訊息的重點大致是這樣：

```
No default constructor for entity 'Snippet'
```

## 外掛替你補上

建置檔加入 `kotlin-jpa` 外掛：

```kotlin
plugins {
    kotlin("plugin.jpa") version "..."
}
```

外掛會對標註了 `@Entity` 的類別，在編譯時自動產生一個無參數建構子。這個建構子只給 JPA 用，你的程式碼裡看不到它，也不能直接呼叫它。類別本身不需要為了遷就 JPA 而改設計。

宣告 `Snippet` 的樣子大致如下：

```kotlin
@Entity
data class Snippet(
    val title: String,
    val language: String,
    @Column(columnDefinition = "TEXT") val code: String,
    val tags: String = "",
    val createdAt: Instant = Instant.now(),
    @Id @GeneratedValue var id: Long? = null,
)
```

為了讓範例簡單，標籤先存成一個字串。`id` 設成可為空，新物件還沒存進資料庫時，它就是 `null`。

## 資料類別當 Entity，有取捨

這裡要誠實講一句：把資料類別直接當 Entity，社群有不同意見。資料類別自動產生的相等判斷與雜湊，會把所有欄位算進去，遇到關聯與延遲載入時可能出現意料外的行為。

本系列只維護一個沒有關聯的 `Snippet`，用資料類別寫起來最簡潔，所以沿用。等你的專案出現關聯，再回頭評估要不要改成一般的類別。

Entity 有了，下一封信我們看另一個看起來像魔法的東西：不寫 SQL，查詢怎麼來的。

回信貼給我你第一次遇到的 Entity 相關錯誤。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。