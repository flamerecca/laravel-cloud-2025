---
category: Spring Boot 基礎教學
status: draft
summary: 類別只管宣告需要什麼，建立與組裝交給容器。用建構子注入搭配 val，是最貼近 Kotlin 的寫法。
tags:
  - Spring Boot
  - Kotlin
  - 依賴注入
  - 電子報
---

# 誰來負責 new

你找遍整個專案，找不到任何一行 `SnippetService()`，它卻在控制器裡好好地被使用。

答案是依賴注入：物件需要的東西，由外面傳進來，自己不負責建立。在 Spring 裡，負責傳進來的是容器。

## 宣告需求，容器組裝

先寫一個服務類別，用 `@Service` 告訴 Spring，這個類別由它管理：

```kotlin
@Service
class SnippetService {
    private val snippets = mutableListOf<Snippet>()

    fun list(): List<Snippet> = snippets
}
```

再寫一個控制器，它需要這個服務：

```kotlin
@RestController
class SnippetController(private val service: SnippetService) {

    @GetMapping("/snippets")
    fun list() = service.list()
}
```

控制器只在建構子宣告「我需要一個 `SnippetService`」。Spring 啟動時發現這個需求，就找到已經管理的服務實例，傳進去。你不需要，也不應該自己 `new`。

建構子只有一個的時候，不需要額外標註。Kotlin 的主建構子搭配 `val`，剛好把依賴宣告成不可變的屬性，比 Java 常見的欄位注入更自然，也讓測試時直接傳入替身變得簡單。

## 三個角色標註，差在哪

`@Component`、`@Service`、`@Repository` 常讓人困惑。它們都讓類別被 Spring 管理，差別主要在語意：

- `@Component`：一般的元件。
- `@Service`：承載業務邏輯的類別。
- `@Repository`：負責存取資料的類別，另外還會把底層資料存取的例外轉換成 Spring 統一的例外型別。

多數時候，換成 `@Component` 程式也跑得動。選對標註的價值在於，打開專案的人一看就知道每個類別的分工。

服務有了，控制器也接上了。下一封信，我們讓這個服務真的接受外部的請求，從五個端點開始。

回信告訴我你習慣用建構子注入，還是欄位注入，原因是什麼。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。
