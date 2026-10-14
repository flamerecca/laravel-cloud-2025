---
category: Spring Boot 基礎教學
status: draft
summary: Kotlin 類別預設不能被繼承，Spring 卻需要替它管理的類別產生代理。kotlin-spring 外掛替你吸收了這個摩擦。
tags:
  - Spring Boot
  - Kotlin
  - kotlin-spring
  - 電子報
---

# 為什麼 Kotlin 類別在 Spring 裡突然要加 open

你照著一篇 Java 的教學寫完設定類別，啟動時 Spring 丟出一個錯誤，說這個類別是 final，不能被繼承。

Java 的類別預設可以被繼承，Kotlin 剛好相反：類別預設是 final，要寫 `open` 才能繼承。Spring 偏偏需要繼承你的類別。

## Spring 為什麼要繼承你的類別

Spring 管理的類別，常常需要附加額外行為，例如交易或設定類別的單例保證。做法是替它產生一個子類別當代理，在子類別裡加上這些行為。類別如果是 final，就沒有子類別可以產生。

這就是錯誤的來源。不啟用任何外掛，把 `@Configuration` 標在 Kotlin 類別上，你會在啟動時看到類似下面的訊息：

```
@Configuration class 'AppConfig' may not be final.
Remove the final modifier to continue.
```

## 一個外掛吸收了這件事

專案建置檔裡那行 `kotlin("plugin.spring")`，就是答案。這個外掛會讓帶有 Spring 標註的類別，編譯時自動變成 open，不需要你一個一個手寫。

```kotlin
// 沒有外掛時，需要自己寫 open
@Configuration
open class AppConfig

// 啟用 kotlin-spring 後，這樣就夠了
@Configuration
class AppConfig
```

外掛涵蓋的是那些帶有 `@Component` 家族標註的類別，`@Service`、`@Controller`、`@Configuration` 都包含在內，也包含幾個常見的需要代理的標註。沒有 Spring 標註的普通類別，外掛不會動它，它們仍然是 final，這是 Kotlin 有意的預設。

所以如果你哪天在一個自己寫的普通類別上遇到類似錯誤，別急著全部加 `open`，先確認它是不是真的需要被 Spring 代理，以及有沒有漏掉標註。

類別可以被 Spring 管理了，下一個問題是：它需要的東西，誰來建立？下一封信談這件事。

回信貼給我你第一次遇到的 Kotlin 與 Spring 的錯誤訊息，我很好奇大家卡在哪。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。
