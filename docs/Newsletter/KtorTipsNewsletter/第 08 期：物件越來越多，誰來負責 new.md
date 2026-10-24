---
category: Ktor 基礎教學
status: draft
summary: 手動組裝與引入 Koin 之間，沒有預設答案。給你三個訊號判斷什麼時候該裝容器，並示範 Koin 的最小接法。
tags:
  - Ktor
  - Kotlin
  - 依賴注入
  - Koin
---

# 物件越來越多，誰來負責 new

專案裡有十幾個類別了。進入點那個 `module()` 函式越寫越長，裡面全是 `val a = A(b, c)` 這種一行一行手動建立物件的程式碼，順序錯了就噴例外。沒有人敢動它。

這時候很多人的直覺是：該裝依賴注入容器了。我想先潑一點冷水，你可能還不需要。

## 手動組裝，其實沒那麼糟

依賴注入的意思是：物件需要的東西由外面傳進來，而不是自己建立。手動組裝就是最直接的做法：

```kotlin
fun Application.module() {
    val orderRepo = OrderRepository()
    val itemRepo = ItemRepository()
    val orderService = OrderService(orderRepo, itemRepo)
    routing { orderRoutes(orderService) }
}
```

它的好處是沒有任何魔法，所有依賴關係一眼可見，IDE 可以直接跳轉，出錯時堆疊追蹤也乾淨。

## 痛起來的三個訊號

以下三個訊號出現兩個以上，再考慮引入容器：

- 物件之間出現交叉依賴，組裝順序變成需要記憶的知識。
- 同一個物件在不同地方被重複建立，你不確定它是不是同一份。
- 測試時要替換某個實作很麻煩，得把一整串建構過程複製一遍。

只有一個訊號，通常只是需要把 `module()` 拆小，而不是需要一個容器。

## Koin 的最小接法

如果真的需要，Koin 是 Ktor 社群常見的選擇，另一個是 Kodein。宣告與使用大概長這樣：

```kotlin
val appModule = module {
    singleOf(::OrderRepository)
    singleOf(::ItemRepository)
    singleOf(::OrderService)
}

fun Application.module() {
    install(Koin) { modules(appModule) }
    val orderService by inject<OrderService>()
    routing { orderRoutes(orderService) }
}
```

建構子需要的依賴由 Koin 自己解析，宣告的順序不再重要。

要誠實講一個取捨：Koin 比較接近服務定位器的風格，依賴有沒有宣告齊全，不是在編譯期發現，而是在啟動或執行時才會發現。團隊可以靠啟動前的檢查工具補強，這個決定要自己做。

容器不是目的，它只是處理一個具體的痛，不痛的時候，不裝也可以。

物件接好之後，下一個問題是怎麼知道誰在呼叫你的 API。下一封信談認證。

回信告訴我你的團隊現在怎麼組裝物件，裝了容器還是手動。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

<!-- 待複核：Koin 與 Ktor 整合所需套件與版本；singleOf 與 install(Koin) 在目前版本的寫法；inject 在 Application 內的取法；Koin 啟動前檢查工具名稱；範例需編譯 -->
