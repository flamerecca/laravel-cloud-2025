---
category: Kotlin Exposed 基礎教學
status: draft
summary: transaction 區塊在 Exposed 裡同時是連線的範圍與提交與還原的邊界，沒有它，操作連執行的機會都沒有。
tags:
  - Kotlin
  - Exposed
  - transaction
  - 電子報
---

# 為什麼每個操作都要包在 transaction 裡

你寫了一行查詢，一執行就丟出錯誤，大意是目前沒有任何 transaction。

很多人第一次遇到時，反應是把查詢包進 `transaction { }`，錯誤消失，然後繼續往下寫，沒有再問為什麼。這封信把那個「為什麼」補上。

## 沒有它，操作無處可去

```kotlin
val total = Snippets.selectAll().count()
```

這一行單獨放在 `main` 裡執行，會丟出類似 `No transaction in context` 的例外。原因很直接：Exposed 的每個資料庫操作，都需要一個目前進行中的交易，才知道要用哪一條連線、送到哪個資料庫。

## 區塊的兩個角色

第一個角色是連線的範圍。進入 `transaction { }` 時，Exposed 取得一條連線，區塊結束時歸還。區塊裡的所有操作共用同一條連線。

第二個角色是提交與還原的邊界。區塊正常結束，變更自動提交。區塊裡丟出例外，所有變更還原，像沒發生過一樣。

## 看一次還原

假設資料表已經建好。下面這段程式，第二個步驟故意丟出例外：

```kotlin
runCatching {
    transaction {
        Snippets.insert {
            it[title] = "會被還原的片段"
            it[language] = "kotlin"
            it[code] = "println(1)"
        }
        error("故意丟出例外")
    }
}

val total = transaction { Snippets.selectAll().count() }
println(total) // 0
```

第一步的新增沒有留下任何痕跡，因為整個區塊被還原了。如果你之後在一個區塊裡做了多件相關的事，要嘛全部成功，要嘛全部沒發生，這個保證就是 transaction 的價值。

所以，它不只是要貼上的樣板，它定義了「一件事」的邊界。區塊包得太大，連線占用太久；包得太小，相關的操作可能一半成功一半失敗。

有了範圍，下一個問題是資料表長什麼樣子。下一封信，我們把資料表定義寫進程式碼。

回信貼給我你第一次遇到的相關錯誤訊息。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

<!-- 待複核：沒有 transaction 時的實際例外型別與訊息，必須在 1.5.1 重現，上面為示意；transaction 的匯入為 org.jetbrains.exposed.v1.jdbc.transactions.transaction；區塊內例外的還原行為需實測；範例尚未實際編譯 -->
