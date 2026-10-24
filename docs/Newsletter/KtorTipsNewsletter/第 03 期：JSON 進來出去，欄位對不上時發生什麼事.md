---
category: Ktor 基礎教學
status: draft
summary: 裝好 ContentNegotiation 之後，缺欄位、多欄位、型別不符三種情況各自會發生什麼事，以及要不要忽略未知欄位的取捨。
tags:
  - Ktor
  - Kotlin
  - JSON
  - 序列化
---

# JSON 進來出去，欄位對不上時發生什麼事

前端同事丟來一句話：「我明明有送 `status` 這個欄位，為什麼你這邊說沒收到？」

這種對話在每個剛接上前端的後端都會發生一次。想回答它，需要先知道一件事：欄位對不上的時候，Ktor 到底會怎麼處理。

## 最小的接法

序列化是把 JSON 字串與 Kotlin 物件互相轉換。要讓 Ktor 做這件事，需要裝 ContentNegotiation 這個 Plugin，並搭配 kotlinx.serialization：

```kotlin
@Serializable
data class CreateOrder(val customerId: Long, val note: String? = null)

install(ContentNegotiation) { json() }

post("/orders") {
    val cmd = call.receive<CreateOrder>()
    call.respond(HttpStatusCode.Created, cmd)
}
```

別忘了在 Gradle 啟用 serialization 的編譯器外掛，沒開的話 `@Serializable` 不會產生任何東西，錯誤訊息還會讓人摸不著頭緒。

## 三種對不上的情況

**缺少必要欄位。** 請求少送了 `customerId`。這個欄位沒有預設值也不可為 null，反序列化直接失敗。反過來，`note` 有預設值，缺了不會有任何影響。

**多出未知欄位。** 請求多帶了一個 `coupon`，資料類別裡沒有它。`Json` 預設是嚴格模式，未知欄位會讓反序列化失敗。

**型別不符。** `customerId` 該是數字，卻送了 `"abc"`。同樣是反序列化失敗。

這三種情況在預設設定下都會導致 `receive` 拋出例外。回給用戶端的是什麼狀態碼、本文帶了什麼訊息，我建議你自己用 `curl` 各送一次看看，不要假設框架替你挑好了對外適合的內容。內部的類別名稱與欄位名稱有可能出現在錯誤訊息裡。

## 要不要忽略未知欄位

嚴格模式的好處是前後端的契約不一致時能馬上發現。壞處是前端多送一個無關欄位，整個請求就失敗。

如果你的用戶端版本不一，或會有第三方呼叫，可以放寬：

```kotlin
install(ContentNegotiation) {
    json(Json { ignoreUnknownKeys = true })
}
```

放寬之後，打錯欄位名稱這類錯誤會變成靜默地被忽略，你得自己承擔這個風險。沒有標準答案，取決於誰在呼叫你的 API。

這封信裡一直出現「裝了一個 Plugin，行為就變了」這件事。下一封信我們把這個機制拆開來看。

回信告訴我你最近一次被欄位不一致卡住，是哪一支 API。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

<!-- 待複核：三種情況在 Ktor 3.6.x 實際回傳的狀態碼與訊息，必須實測；receive 失敗拋出的例外類型；ignoreUnknownKeys 設定名稱；範例需實際編譯 -->
