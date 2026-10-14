---
category: Kotlin Exposed 基礎教學
status: draft
summary: DAO 把資料表的每一列包成物件，用屬性與方法操作資料。它底層用的，仍然是同一份資料表定義。
tags:
  - Kotlin
  - Exposed
  - DAO
  - Entity
---

# 換一種寫法，DAO 的 Entity

你寫了一堆把資料列轉成物件的程式，每個欄位都得手動對應一次。

前面幾封信用的是 DSL，直接操作資料表與欄位。這封信換成 DAO，讓同一張資料表，用物件的方式來碰。

## 宣告一個 Entity

資料表定義不用重寫，直接沿用前面宣告的 `Snippets`。只需要再宣告一個對應的 Entity：

```kotlin
class Snippet(id: EntityID<Int>) : IntEntity(id) {
    companion object : IntEntityClass<Snippet>(Snippets)

    var title by Snippets.title
    var language by Snippets.language
    var code by Snippets.code
}
```

`Snippet` 是一列資料的物件版本，每個 `var` 用 `by` 委派給資料表定義裡的對應欄位。`companion object` 是這個類別的入口，新增與查詢都從它出發。

## 用物件操作

```kotlin
transaction {
    val snippet = Snippet.new {
        title = "日期格式化"
        language = "kotlin"
        code = "LocalDate.now()"
    }
    snippet.title = "新的標題"
    val found = Snippet.find { Snippets.language eq "kotlin" }.toList()
    println("${found.size} 筆")
}
```

`Snippet.new` 新增一筆，直接改 `snippet.title` 就是更新，不需要另外呼叫更新函式。Exposed 會在適當的時機把變更送進資料庫。查詢用 `find`，條件寫法和 DSL 一樣。

## 底層仍然是同一套

DAO 看起來像是另一個世界，其實它最終仍然產生 SQL，只是替你包了一層。所以除錯的方法一樣：開啟 SQL 日誌，看實際送出了什麼。後面第 11 期會用到這個習慣。

兩種風格都看過了，下一封信，我們把它們並排放在一起比較。

回信告訴我，你比較習慣哪一種風格。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

<!-- 待複核：IntEntity、IntEntityClass 與 EntityID 在 1.5.1 的匯入，預期分別在 v1.dao 與 v1.core.dao.id；DAO 是否需要額外的依賴模組；new 與 find 的簽名；範例尚未實際編譯 -->
