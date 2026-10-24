---
category: Kotlin Exposed 基礎教學
status: draft
summary: Exposed 是 JetBrains 維護的 Kotlin 資料庫函式庫，用型別安全的方式描述 SQL，並提供 DSL 與 DAO 兩種寫法。
tags:
  - Kotlin
  - Exposed
  - 資料庫
  - 電子報
---

# 不想拼 SQL 字串，也不想被 ORM 綁架

欄位名稱在字串裡打錯一個字母，程式照樣編譯，一直跑到那段查詢真的被執行，才在資料庫那邊炸開。

如果你手上有一堆拼接 SQL 字串的 Kotlin 程式，又擔心換成 ORM 之後，查詢會被藏在你看不見的地方，這個系列要介紹的 Exposed，剛好落在兩者之間。

## 同一個查詢，兩種寫法

先看字串版本，注意 `language` 被打錯了：

```kotlin
val sql = "SELECT title FROM snippets WHERE langauge = 'kotlin'"
```

這行在編譯期沒有任何問題，執行時才會被資料庫退回來。

換成 Exposed 的寫法：

```kotlin
Snippets.selectAll().where { Snippets.language eq "kotlin" }
```

`Snippets.language` 是程式碼裡宣告過的欄位。如果打成 `Snippets.langauge`，編譯器當場就會告訴你，不用等到執行。

## 兩種風格，一個函式庫

Exposed 提供兩種和資料庫互動的方式。

第一種叫 DSL，領域特定語言。它長得像 SQL，每個函式對應一個 SQL 子句，讀得懂 SQL 就讀得懂它。上面的例子就是 DSL。

第二種叫 DAO，資料存取物件。它把資料表的每一列包成物件，讓你用屬性與方法操作資料，比較接近你在 Eloquent 或 JPA 裡熟悉的感覺。

兩種風格底層用的是同一份資料表定義，也都會產生 SQL。這個系列會依序走過兩者，之後再來討論怎麼選。

## 誠實的提醒

Exposed 不是萬用的。它的學習成本不低，關聯與交易也需要你自己搞清楚規則，沒有任何東西會替你擋下寫得很糟的查詢。

它換來的是兩件事：欄位名稱與型別在編譯期被檢查，以及查詢不會被藏起來，你看得到每一條實際送出去的 SQL。如果這兩件事對你有價值，這個系列值得追。

下一封信，我們先把環境跑起來。第一次連上資料庫，只需要一行。

回信告訴我，你現在怎麼存取資料庫，是原生 SQL、某個 ORM，還是其他工具。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

<!-- 待複核：Exposed 維護者與支援的資料庫清單，對照官方文件；對照片段尚未以 1.5.1 實際編譯，selectAll().where 與 eq 的匯入需實測；本期預設鎖定 1.5.1 -->
