---
category: Spring Boot 基礎教學
status: draft
summary: 用 @SpringBootTest 與 MockMvc 寫兩個最小測試，把每次改版都要手動送一遍的請求，變成可以重複執行的保護。
tags:
  - Spring Boot
  - Kotlin
  - 測試
  - MockMvc
---

# 不用每次改完都手動點 Postman

改了一行程式，你又把那五個請求，一個一個在 Postman 裡手動送了一遍。

下次再改，你還是會送一遍，或者乾脆懶得送。把那幾步寫成測試，這件事就不用再靠意志力。

## 一個會模擬請求的測試

`MockMvc` 可以在不真的開網路連線的情況下，模擬一次 HTTP 請求，並檢查回應。搭配 `@SpringBootTest`，它會啟動你的應用程式，所以控制器、服務與資料庫都是真實接起來的。

第一個測試：新增片段，應該回 201。

```kotlin
@SpringBootTest
@AutoConfigureMockMvc
class SnippetApiTest(@Autowired val mockMvc: MockMvc) {

    @Test
    fun `新增片段回 201`() {
        mockMvc.post("/snippets") {
            contentType = MediaType.APPLICATION_JSON
            content = """{"title":"日期格式化","language":"kotlin","code":"LocalDate.now()"}"""
        }.andExpect { status { isCreated() } }
    }
}
```

第二個測試：查詢一個不存在的片段，應該回 404，而且錯誤的格式要符合上一期約定的樣子：

```kotlin
@Test
fun `查詢不存在的片段回 404`() {
    mockMvc.get("/snippets/9999")
        .andExpect {
            status { isNotFound() }
            jsonPath("$.code") { value("SNIPPET_NOT_FOUND") }
        }
}
```

跑一次測試，兩個都通過。這代表你過去手動送過的那兩個請求，現在每次改版都會自動替你送一遍。

## 這只是起手式

這兩個測試示範的是最小可行的做法：從外面送請求，檢查狀態碼與回應。更完整的測試策略，例如替換掉真實的資料庫、測更細的單元，不在這個系列的範圍。先把最常手動確認的幾個情境變成測試，已經能擋下很多意外。

十一封信走完，下一封是這個系列的最後一期，我會把前面所有東西，收斂成一份可以直接對照你專案的清單。

回信告訴我，你最想先自動化的是哪一次手動測試。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

<!-- 待複核：@SpringBootTest 與 MockMvc 在鎖定版本的注入與套件寫法，Boot 版本間 AutoConfigureMockMvc 的套件路徑可能不同；Kotlin MockMvc DSL 的 import；測試是否污染開發資料；範例需實際執行通過 -->
