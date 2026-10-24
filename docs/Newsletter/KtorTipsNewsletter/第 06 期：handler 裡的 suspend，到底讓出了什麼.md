---
category: Ktor 基礎教學
status: draft
summary: 寫了 suspend 並不代表沒有卡住。用兩個只差一行的 handler 看出掛起與阻塞的差別，再學平行查詢的正確寫法。
tags:
  - Ktor
  - Kotlin
  - 協程
  - suspend
---

# handler 裡的 suspend，到底讓出了什麼

壓測結果出來，跟舊服務差不多。你納悶：我明明每個處理函式都寫成 `suspend` 了。

`suspend` 這個關鍵字本身不會讓任何東西變快。它只是讓函式有資格在等待時讓出執行緒，要不要讓，取決於函式裡面實際呼叫了什麼。

## 兩個只差一行的 handler

先用 Kotlin 的說法交代一下：`suspend` 函式在等待結果時可以掛起，把執行緒讓給別人用，等結果回來再接著跑。阻塞則是執行緒本身被卡在那裡。看這兩個：

```kotlin
get("/blocking") { Thread.sleep(2_000); call.respondText("done") }
get("/suspending") { delay(2_000); call.respondText("done") }
```

兩個 handler 單獨呼叫，都是兩秒後回應，看不出差別。差別在同時有很多請求的時候。

我建議你自己做這個實驗，不要只信我：用你慣用的壓測工具，同時送出幾十個請求給 `/blocking`，再對 `/suspending` 做同樣的事，比較總共花多久。`Thread.sleep` 會占住處理請求的執行緒，Netty 搭配 Ktor 預設只有少量執行緒在處理呼叫，一旦被占滿，後面的請求就得排隊。`delay` 掛起之後執行緒會被放走，可以去服務別的請求。

真實的服務裡不會有人寫 `Thread.sleep`，但會有人呼叫阻塞式的資料庫驅動、舊的 SDK 或同步的檔案讀寫，效果跟它是一樣的。

## 平行查詢的正確寫法

handler 裡常見的需求是同時發出兩個查詢，再合併結果：

```kotlin
get("/orders/{id}") {
    val id = call.parameters.getOrFail<Long>("id")
    val view = coroutineScope {
        val order = async { orderRepository.find(id) }
        val items = async { itemRepository.findByOrder(id) }
        OrderView(order.await(), items.await())
    }
    call.respond(view)
}
```

為什麼要包 `coroutineScope`？`async` 必須在某個協程範圍裡才能呼叫，而 handler 的接收者型別在不同版本裡並不相同。用 `coroutineScope` 包起來，在任何版本都成立，同時帶來結構化並發的好處：任何一個子工作失敗，另一個會被取消，例外也會一路傳出來，不會留下孤兒工作。

## 躲不掉的阻塞呼叫怎麼辦

有些函式庫就是阻塞式的，換不掉。這時候用 `withContext(Dispatchers.IO)` 把它隔離到專供阻塞工作的那組執行緒：

```kotlin
val report = withContext(Dispatchers.IO) { legacyClient.fetch(id) }
```

這是止血，阻塞本身還在，只是被限制在一個範圍內，不會拖累處理請求的主要執行緒。能換成非阻塞的函式庫，還是換掉比較好。

說到阻塞，最常見的來源就是資料庫。下一封信談怎麼把它接進來。

回信檢查一下你的專案，有沒有在 handler 裡直接呼叫阻塞式函式庫的地方，告訴我你找到什麼。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

<!-- 待複核：Ktor 3.6.x 路由 handler 的接收者型別，直接呼叫 async 是否可編譯；預設 Netty 事件迴圈與呼叫處理執行緒數量；Thread.sleep 對照實驗在預設設定下是否能觀察到排隊，必須實測；getOrFail 套件 -->
