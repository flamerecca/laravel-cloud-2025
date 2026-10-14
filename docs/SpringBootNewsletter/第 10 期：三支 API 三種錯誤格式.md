---
category: Spring Boot 基礎教學
status: draft
summary: 用 @RestControllerAdvice 把確認失敗與查無資料統一轉成固定形狀的錯誤回應，內部細節只留在日誌。
tags:
  - Spring Boot
  - Kotlin
  - 例外處理
  - RestControllerAdvice
---

# 三支 API 三種錯誤格式

前端同事問你：這三支 API 的錯誤，為什麼各長各的樣子？

一支回純文字，一支回框架預設的 JSON，一支回你自己拼的欄位。每個控制器方法各自處理例外的結果，就是這樣。

## 先約定一個形狀

定義一個全服務共用的錯誤資料類別：

```kotlin
data class ErrorBody(val code: String, val message: String)
```

`code` 給程式判斷，`message` 給人看。前端只需要寫一個錯誤處理函式。

## 集中在一個地方處理

`@RestControllerAdvice` 讓一個類別集中處理所有控制器丟出的例外。先定義一個查無資料的例外，服務在找不到時丟出它：

```kotlin
class SnippetNotFoundException(val id: Long) : RuntimeException()
```

再寫處理類別：

```kotlin
@RestControllerAdvice
class GlobalExceptionHandler {

    @ExceptionHandler(MethodArgumentNotValidException::class)
    fun invalid(e: MethodArgumentNotValidException) =
        ResponseEntity.badRequest().body(
            ErrorBody("INVALID_REQUEST", e.bindingResult.fieldErrors.joinToString { "${it.field} ${it.defaultMessage}" })
        )

    @ExceptionHandler(SnippetNotFoundException::class)
    fun notFound(e: SnippetNotFoundException) =
        ResponseEntity.status(404).body(ErrorBody("SNIPPET_NOT_FOUND", "找不到片段 ${e.id}"))
}
```

驗證失敗回 400，查無資料回 404，兩種都用同一個形狀。控制器裡不需要再寫任何錯誤處理，丟出例外就好。

## 兜底，與一個提醒

你可以再加一個處理 `Exception` 的方法當兜底，回固定的 500 與一段通用文字，同時把完整的例外記到日誌。重點是：堆疊追蹤與內部訊息只該留在日誌，不該出現在回應裡。

加兜底之前要先確認一件事：它會不會連框架自己處理的例外，例如路徑不存在，也一起接走，讓原本應該是 404 的回應變成 500。請在你的版本上實測。

錯誤處理好了，剩下的問題是：你怎麼知道下次改版之後，這些行為還是對的？下一封信談測試。

回信告訴我，你的團隊現在的錯誤格式有幾種。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

<!-- 待複核：確認失敗時拋出的例外型別與取得欄位錯誤的寫法；兜底處理器是否影響框架自己處理的例外，必須實測；範例需實際執行並確認各情境回應 -->
