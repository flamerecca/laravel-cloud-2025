---
category: Ktor 基礎教學
status: draft
summary: Plugin 在請求管線上的位置、安裝順序為什麼重要、怎麼自己寫一個，並看一眼 install 函式本身到底做了什麼。
tags:
  - Ktor
  - Kotlin
  - Plugin
  - 原始碼
---

# install() 一下，你的請求就多了一層

你 `install` 了五個 Plugin，請問它們誰先誰後？大多數人不太確定，反正跑得起來就好。直到有一天，某個 Plugin 的行為跟預期不一樣，才發現順序比想像中重要。

## 請求穿過一串 Plugin

如果你用過 Laravel 的 Middleware 或 Spring 的 Filter，概念是一樣的。請求進來之後，先依序穿過已安裝的 Plugin，抵達你寫的路由處理函式，回應再從管線裡出去。每一個 Plugin 可以在這條路上的特定時間點插手，改寫請求、記錄資訊、攔截例外，或直接回應。

安裝順序會影響行為，因為同一個時間點上，Plugin 是依安裝先後處理的。一個實際的後果是：想量測整條管線耗時的 Plugin，需要排在前面，排在後面的話，它看不到前面 Plugin 花掉的時間。

## 十幾行寫一個自己的 Plugin

下面是一個記錄每個請求耗時的 Plugin：

```kotlin
private val StartKey = AttributeKey<Long>("RequestTimerStart")

val RequestTimer = createApplicationPlugin(name = "RequestTimer") {
    onCall { call -> call.attributes.put(StartKey, System.nanoTime()) }
    onCallRespond { call, _ ->
        val ms = (System.nanoTime() - call.attributes[StartKey]) / 1_000_000
        call.application.log.info("${call.request.path()} 花了 $ms ms")
    }
}
```

用 `createApplicationPlugin` 宣告名稱，再用 `onCall` 與 `onCallRespond` 掛在請求進入與回應送出兩個時間點。使用時一行：

```kotlin
install(RequestTimer)
```

## install 本身做了什麼

最後看一眼 `install`。這是 Ktor 3.6.0 裡這個函式的主體，我只拿掉了註解與一段路由專用分支：

```kotlin
public fun <P : Pipeline<*, PipelineCall>, B : Any, F : Any> P.install(
    plugin: Plugin<P, B, F>,
    configure: B.() -> Unit = {}
): F {
    val registry = pluginRegistry
    return when (val installedPlugin = registry.getOrNull(plugin.key)) {
        null -> {
            val installed = plugin.install(this, configure)
            registry.put(plugin.key, installed)
            installed
        }
        plugin -> installedPlugin
        else -> throw DuplicatePluginException("...")
    }
}
```

邏輯其實很單純：先用 Plugin 的 key 去登錄表查有沒有裝過。沒裝過，就呼叫 Plugin 自己的 `install`，讓它把處理邏輯掛進管線，並登記起來。已經裝過同一個，直接回傳既有的。key 相同卻是不同的 Plugin，丟出重複例外。

所謂「Plugin 之間互不干擾」，靠的就是每個 Plugin 只往自己的 key 底下登記，並各自掛在管線的時間點上。原始碼在 `ktor-server-core` 模組的 `ApplicationPlugin.kt`，想深入的話從這裡開始讀。

請求穿過管線之後，處理函式裡如果丟出例外，使用者會看到什麼？下一封信談這件事。

回信告訴我你最想自己寫的那個 Plugin 是什麼。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

<!-- 待複核：install 原始碼片段取自 ktorio/ktor 3.6.0 ApplicationPlugin.kt，已省略註解與 RoutingNode 分支，發稿前請與原檔核對；createApplicationPlugin、onCall、onCallRespond 簽名；Plugin 執行順序是否純由安裝順序決定，需實測；自訂 Plugin 範例需編譯 -->
