---
category: Spring Boot 基礎教學
status: draft
summary: 在請求資料類別加上必填與長度規則，用 @Valid 觸發。Kotlin 下註解套用的位置有一個陷阱，寫完一定要送一次錯的資料確認它有效。
tags:
  - Spring Boot
  - Kotlin
  - Bean Validation
  - 電子報
---

# 標題留空，API 應該回什麼

你送出一個標題是空字串的片段，API 回你 201，資料庫裡多了一筆沒有標題的資料。

這封信替請求資料加上規則，並提醒你一個 Kotlin 特有的陷阱：規則寫了，卻可能完全沒有生效。

## 把規則寫在資料類別上

先在建置檔加入驗證用的依賴，`spring-boot-starter-validation`。接著在請求資料類別標上規則：

```kotlin
data class CreateSnippetRequest(
    @field:NotBlank val title: String,
    @field:NotBlank val code: String,
    val language: String,
    val tags: List<String> = emptyList(),
)
```

最後在控制器的參數加上 `@Valid`，告訴 Spring 在收到請求時，依規則確認一次：

```kotlin
@PostMapping
fun create(@Valid @RequestBody request: CreateSnippetRequest) = service.create(request)
```

## 陷阱：註解要寫成 @field:

注意上面寫的是 `@field:NotBlank`，前面多了 `field:`。在 Kotlin 裡，寫在主建構子屬性上的註解，如果沒有指定套用位置，預設會先套用到建構子的參數，背後的欄位反而沒有被標上。確認規則看的是欄位，結果你的規則被套在別的地方，沒有任何錯誤，也沒有任何作用。

所以寫完規則，請做一件事：實際送一個空標題的請求，看看有沒有被擋下。不要只看程式碼覺得對就算了。

## 失敗時回什麼

規則生效後，空標題的請求會得到 400，本文帶有框架預設的錯誤資訊。這份預設的內容通常資訊很多，格式也跟你之後會自己設計的錯誤不一致。下一封信，我們把所有錯誤收攏成同一種格式。

回信告訴我，你的專案裡最常漏掉的欄位確認是哪一個。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。
