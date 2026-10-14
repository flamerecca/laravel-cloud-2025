---
category: Spring Boot 基礎教學
status: draft
summary: 把服務類別從操作記憶體列表改成呼叫 Repository，控制器一行不動。分層做對了，換資料來源只動一層。
tags:
  - Spring Boot
  - Kotlin
  - H2
  - 分層
---

# 把記憶體換成資料庫，API 一行都不用改

你重啟了一次服務，剛剛新增的片段全不見了。

這是因為到目前為止，片段都存在一個記憶體裡的列表。這封信把它換成真正的資料庫，而且只動一個類別。

## 只改服務類別

原本的服務操作一個 `mutableListOf`。現在改成呼叫上一期寫的 `SnippetRepository`：

```kotlin
@Service
class SnippetService(private val repository: SnippetRepository) {

    fun list(language: String?): List<Snippet> =
        if (language == null) repository.findAll() else repository.findByLanguage(language)

    fun find(id: Long): Snippet? = repository.findById(id).orElse(null)

    fun create(request: CreateSnippetRequest): Snippet =
        repository.save(Snippet(request.title, request.language, request.code))
}
```

控制器不動，請求資料類別不動，所有端點的路徑與回應格式完全不變。你只是把「資料放在哪裡」這個決定，從服務類別的內部，移到了另一個介面後面。這就是分層的收益：每一層只關心自己的責任，換掉其中一層，其他層不受影響。

## 資料庫連線設定

這個系列用內嵌的 H2，一個跑在記憶體裡的資料庫，不用安裝任何東西。在建置檔加入依賴：

```kotlin
runtimeOnly("com.h2database:h2")
```

設定檔指定連線，並讓 JPA 依 Entity 自動建立資料表：

```
spring.datasource.url=jdbc:h2:mem:snippetvault
spring.jpa.hibernate.ddl-auto=update
```

## H2 的定位

H2 適合學習與開發，因為它零安裝、啟動快。但它預設把資料放在記憶體，應用程式一停，資料就沒了，這跟剛剛那個列表的下場一樣。也就是說，這一期換掉的是「資料存取的寫法」，要讓資料真的留下來，還需要換成一個獨立運作的資料庫，這部分不在這個系列範圍，只提醒你正式環境不能用它。

資料現在進得來了，可是什麼都進得來，包括空標題。下一封信處理這件事。

回信告訴我，你下一步想把它換成哪一種資料庫。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。
