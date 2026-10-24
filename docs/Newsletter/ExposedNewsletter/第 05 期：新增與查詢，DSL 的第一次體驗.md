---
category: Kotlin Exposed 基礎教學
status: draft
summary: insert、selectAll 與 where 三個函式，組成最小的資料存取。讀得懂 SQL，就讀得懂它們。
tags:
  - Kotlin
  - Exposed
  - DSL
  - 電子報
---

# 新增與查詢，DSL 的第一次體驗

你在網路上查到三種寫法，不知道哪一種現在還能用。

Exposed 在 1.0 版調整過套件路徑與部分寫法，舊文章的程式碼很容易直接編譯失敗。這封信用 1.5.1 的寫法，把最基本的新增與查詢走一遍。

## 新增一筆

```kotlin
transaction {
    val id = Snippets.insertAndGetId {
        it[title] = "日期格式化"
        it[language] = "kotlin"
        it[code] = "LocalDate.now()"
    }
    println("新增了一筆，ID 是 $id")
}
```

`insertAndGetId` 對應 SQL 的 INSERT，區塊裡每一行指定一個欄位的值。因為主鍵是自動遞增的，不用也不能自己指定，新增完成後會回傳資料庫產生的 ID。

## 查詢

```kotlin
transaction {
    Snippets.selectAll()
        .where { Snippets.language eq "kotlin" }
        .map { it[Snippets.title] }
}
```

`selectAll` 從資料表取出所有欄位，`where` 加上條件，`map` 把每一列轉成你要的東西。這裡條件用的 `eq`，需要明確匯入，1.0 之後這類運算子已經改成頂層函式。

這段程式會產生的 SQL，大致等於：

```sql
SELECT snippets.id, snippets.title, snippets.language, snippets.code
FROM snippets WHERE snippets.language = 'kotlin'
```

你可以逐個函式對照。這就是 DSL 的意思：它沒有發明新語法，只是把 SQL 換成了 Kotlin 的函式呼叫，並讓編譯器替你檢查欄位。

## 一個提醒

如果你查到的範例寫成 `Snippets.select { ... }`，那是舊版的寫法，在新版已經被取代。遇到這類差異，先確認文章的 Exposed 版本，再決定能不能直接照抄。

新增與查詢都能用了。下一封信處理更危險的兩件事，更新與刪除。

回信告訴我，你最想先試哪一種查詢條件。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

<!-- 待複核：insertAndGetId、selectAll、where 與 eq 在 1.5.1 的匯入與簽名；select { } 是否已移除或僅標示過時；產生的 SQL 為示意，需用日誌實際核對；範例尚未實際編譯 -->
