---
category: Spring Boot 基礎教學
status: draft
summary: 繼承 JpaRepository 就有基本 CRUD，方法名稱本身就是查詢的描述。看懂命名慣例，也知道什麼時候該放棄這種寫法。
tags:
  - Spring Boot
  - Kotlin
  - Spring Data JPA
  - 電子報
---

# 不寫 SQL，findByLanguage 為什麼就能查

你只寫了一行介面宣告，沒有任何實作，它卻真的查得出資料。

這看起來像魔法，所以很多人不敢相信，也不敢改。其實規則很單純，值得搞懂。

## 繼承就有基本能力

宣告一個介面，繼承 `JpaRepository`，指定 Entity 與主鍵的型別：

```kotlin
interface SnippetRepository : JpaRepository<Snippet, Long>
```

這一行就取得了儲存、依 ID 查詢、查全部、刪除等常用方法。你不需要寫實作類別，Spring 啟動時會自動替這個介面產生一個。

## 方法名稱就是查詢

想依程式語言查片段，只要在介面加一個方法：

```kotlin
interface SnippetRepository : JpaRepository<Snippet, Long> {
    fun findByLanguage(language: String): List<Snippet>
}
```

Spring 會把方法名稱拆開來解析：`findBy` 表示查詢，`Language` 對應 Entity 的 `language` 欄位，參數就是查詢條件。換成資料庫的查詢，大致等於「找出 language 等於某個值的所有片段」。

條件可以組合：

```kotlin
fun findByLanguageAndTitleContaining(language: String, title: String): List<Snippet>
```

`And` 連接兩個條件，`Containing` 表示標題包含某個字串。還有 `OrderBy`、`IsNull` 等關鍵字可以用。

## 什麼時候該放棄

這種寫法有界線。當方法名稱長到需要折行才讀得完，或條件複雜到名稱已經不像一句人話，就是該換寫法的時候。可以改用 `@Query` 直接寫查詢，讓意圖更清楚。名稱是用來描述查詢的，不是用來把整個查詢硬塞進去。

另外有一個提醒：欄位名稱打錯，在應用程式啟動時就會報錯，不會等到執行到那個查詢才發現。這是件好事，你不會把一個壞掉的查詢放進正式環境。

介面有了，下一封信我們把前面那個放在記憶體裡的服務，換成真正的資料庫。

回信告訴我，有哪一個查詢條件，你想試試看用方法名稱表達。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。
