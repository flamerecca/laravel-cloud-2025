---
category: FastAPI 基礎教學
status: draft
summary: 說明 async def 路由跑在事件迴圈、一般 def 路由跑在執行緒池，因此在 async def 裡呼叫阻塞函式會讓其他請求全部排隊，並給出選擇規則與補救做法。
tags:
  - FastAPI
  - Python
  - async
  - 事件迴圈
  - 執行緒池
---

# async def 還是 def，寫錯會卡住整台伺服器

你把路由全部改成 `async def`，重新壓測，數字一點都沒變，有時甚至更慢。我猜你是看到文件範例都這樣寫，就跟著改了。

問題出在，`async def` 只是換了一個跑法，不會自動讓裡面的程式碼變快。跑法選錯，一個請求就能讓其他請求全部排隊。

## 一個服務生顧很多桌

先用一句話交代背景：`async def` 路由跑在事件迴圈上，事件迴圈就像一個服務生同時顧很多桌，他一次只能做一件事，只有遇到 `await`，也就是客人說「我還要想一下」的時候，他才會轉身去服務別桌。

如果你在 `async def` 裡呼叫的是不會讓出控制權的阻塞函式，例如 `time.sleep`，服務生就會站在那一桌前面乾等，整間店都停擺。

一般 `def` 路由的處理方式不同。依官方文件，FastAPI 會把它丟進外部的執行緒池執行，再 await 結果，所以阻塞只會卡住那一條執行緒，事件迴圈照常轉。

簡單說：`async def` 直接在事件迴圈上跑，`def` 被丟進執行緒池跑。

## 三支路由，各等 1 秒

下面三支路由都模擬一個要等 1 秒的工作，差別只在寫法：

```python
import asyncio, time
from fastapi import FastAPI

app = FastAPI()

@app.get("/a")
async def blocking_in_async():
    time.sleep(1)
    return {"ok": True}

@app.get("/b")
async def awaiting_in_async():
    await asyncio.sleep(1)
    return {"ok": True}

@app.get("/c")
def blocking_in_def():
    time.sleep(1)
    return {"ok": True}
```

同時對每支路由送出 10 個請求，以程式邏輯推算，總耗時會是這樣：

- `/a`：`time.sleep` 卡住事件迴圈，10 個請求一個接一個，理論上約 10 秒。
- `/b`：`await asyncio.sleep` 讓出控制權，10 個請求同時在等，理論上約 1 秒。
- `/c`：10 條執行緒各睡各的，理論上約 1 秒。

以上都是理論值，實測數字待補。這裡也能看出一件事：`/c` 沒有用到任何 `async`，卻跟 `/b` 一樣快。

<!-- 圖表佔位：三種寫法與總耗時的長條圖，橫軸為 /a、/b、/c，縱軸為同時送出 10 個請求的總耗時。數字以實測為準，目前為理論值約 10 秒、約 1 秒、約 1 秒，實測數字待補 -->

## 怎麼選

規則很短：

- 用的函式庫支援 `await`，就用 `async def`。
- 用的是同步函式庫，就用 `def`。
- 不確定的時候，用 `def`。

官方文件給的建議也是這個方向，連「If you just don't know, use normal `def`」都寫在文件裡。大多數資料庫驅動程式和不少第三方 SDK 目前仍是同步的，所以你手上的路由可能比想像中更適合用 `def`。

`def` 的代價是執行緒池有上限。依 Starlette 文件，預設只有 40 個名額，同時超過 40 個阻塞請求還是會排隊，只是比整個事件迴圈卡死好得多。

## 非得在 async def 裡呼叫阻塞程式

有時候路由本身需要 `await` 別的東西，中間卻夾了一段沒有非同步版本的同步呼叫。這時把那一段丟到執行緒去跑：

```python
import asyncio, time

def slow_report():
    time.sleep(1)
    return "done"

@app.get("/d")
async def report():
    result = await asyncio.to_thread(slow_report)
    return {"result": result}
```

FastAPI 也提供 `run_in_threadpool`，用法類似，從 `fastapi.concurrency` 匯入。兩者都能讓阻塞段落離開事件迴圈，其餘的 `await` 照舊。

## 這封信帶走的東西

`async def` 的前提是裡面真的有東西可以 `await`。沒有的話，用 `def`，讓 FastAPI 替你把它放進執行緒池。

資料庫驅動程式就是最常見的阻塞來源。下一封信會談資料庫連線，以及為什麼一個請求該對應一個 Session。

回信告訴我，你的服務裡用了哪些同步函式庫，是資料庫驅動程式、HTTP 客戶端，還是某個雲端 SDK。我想知道大家最常在哪裡踩到。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

---

## 待實測項目

以下項目我無法執行程式碼，請在 Python 3.12 與目前 FastAPI 版本實測後再發信：

1. 三支路由的耗時數字全部是理論值。請用 `uvicorn` 單一 worker 啟動，對 `/a`、`/b`、`/c` 各同時送出 10 個請求，記錄總耗時，填回圖表佔位與正文「實測數字待補」處。實測環境需註明機器型號、Python 版本、FastAPI 版本與 `uvicorn` 啟動參數。
2. `/a` 的症狀請另外確認：同時打開 `/b` 或 `/docs`，確認在 `/a` 執行期間是否也跟著卡住。
3. 第一段與第二段程式碼均未實際執行，需確認可正常啟動。第二段範例中的 `app` 沿用第一段，單獨貼出時需補上 `from fastapi import FastAPI` 與 `app = FastAPI()`。
4. 「預設 40 個名額」依 Starlette 文件，需確認目前 FastAPI 與 Starlette 版本是否仍為 40，並可實測同時 41 個阻塞請求的行為。
5. `run_in_threadpool` 從 `fastapi.concurrency` 匯入，本次未在官方文件查到，需實測匯入與用法，或改引用 Starlette 的 `starlette.concurrency.run_in_threadpool`。
6. `asyncio.to_thread` 使用的是 asyncio 預設 executor，與 Starlette 的 anyio 執行緒名額是否共用，本次未查到，稿中未做任何共用的說法，需確認。
7. 「If you just don't know, use normal `def`」引自官方 async 頁面，發信當天需再看一次頁面確認措辭仍在。
