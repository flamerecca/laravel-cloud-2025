---
category: FastAPI 基礎教學
status: draft
summary: 用 Depends 把重複的分頁參數與取使用者邏輯抽成依賴函式，並說明子依賴的呼叫順序與同一請求內的快取。
tags:
  - FastAPI
  - Python
  - Depends
  - 依賴注入
---

# Depends 一下，共用邏輯就不用到處複製

在專案裡 grep 一下 `limit = min(limit, 100)`，我猜你會找到三行一模一樣的結果，分別躺在書籍列表、借閱紀錄和會員列表三支路由裡。某天產品說上限要改成 50，你得改三個地方，還得祈禱沒有漏掉第四個。

如果你剛從 Laravel 或 Spring 轉過來，大概也在找：中介層在哪？Bean 在哪？FastAPI 沒有這兩樣東西的直接對應，它用的是 `Depends`。

## 重複的長相

三支路由各自宣告 `skip`、`limit`，再各自檢查上限：

```python
@app.get("/books")
def list_books(skip: int = 0, limit: int = 20):
    limit = min(limit, 100)
    return {"skip": skip, "limit": limit}
```

這一段複製三次，就是你現在的處境。

## 把它抽成依賴函式

做法是把這段邏輯放進一個普通函式，再讓路由在簽章上宣告「我需要它的回傳值」：

```python
from typing import Annotated
from fastapi import Depends, FastAPI

app = FastAPI()

def pagination(skip: int = 0, limit: int = 20):
    return {"skip": skip, "limit": min(limit, 100)}

Page = Annotated[dict, Depends(pagination)]

@app.get("/books")
def list_books(page: Page):
    return page

@app.get("/loans")
def list_loans(page: Page):
    return page
```

`pagination` 就是一個函式，沒有繼承任何東西，也不用註冊到哪裡。注意 `Depends(pagination)` 傳進去的是函式本身，後面沒有括號，因為呼叫它是 FastAPI 的工作。

每次請求進來，FastAPI 會先看路由函式的參數，發現 `page` 需要 `pagination`，就去讀 `skip`、`limit` 這兩個查詢參數，呼叫 `pagination`，再把回傳值塞給 `page`。`pagination` 的參數規則和路由函式一樣，型別提示照樣會變成確認規則。上限改成 50，只動一行。

這種在函式簽章上宣告需要什麼、由框架在請求當下呼叫並注入結果的機制，就是 FastAPI 的依賴注入。

<!-- 圖表佔位：依賴呼叫順序流程圖。請求進來 → FastAPI 解析路由函式簽章上的依賴 → 呼叫 pagination 取得結果 → 帶著結果呼叫路由函式 → 回應 -->

## 依賴可以再依賴

真實的服務不會只有分頁。假設借閱要先知道目前是誰，再算他還能借幾本：

```python
from fastapi import Header

def get_user(x_user: Annotated[str, Header()]):
    return x_user

def get_quota(user: Annotated[str, Depends(get_user)]):
    return {"user": user, "quota": 5}

@app.get("/loans/quota")
def read_quota(
    q: Annotated[dict, Depends(get_quota)],
    user: Annotated[str, Depends(get_user)],
):
    return q
```

路由只宣告了 `get_quota` 和 `get_user`，但 FastAPI 會自己排出順序：先呼叫 `get_user`，把結果交給 `get_quota`，最後才輪到路由函式。你不用手動串。

留意 `get_user` 在這裡被用了兩次，一次來自 `get_quota`，一次直接來自路由。官方文件的說法是，同一個依賴在同一支路由的同一次請求裡被多處宣告時，只會呼叫一次，結果會快取起來分給所有需要的人。所以不必擔心重複查使用者。若你就是要每次都重新呼叫，`Depends` 有一個 `use_cache=False` 參數可以關掉快取。

## 對照你熟悉的東西

Laravel 的服務容器和 Spring 的 Bean，通常要先註冊、再由容器在啟動時或請求時解析。FastAPI 的依賴就是函式，不需要註冊，每次請求才解析。你可以把它想成：把原本寫在路由開頭的那幾行程式碼，搬到一個有名字的函式裡，再用簽章把它接回來。

至於 `Annotated` 寫法，官方文件目前建議優先使用，舊式的 `page: dict = Depends(pagination)` 仍然存在，但在文件裡被放在次要位置。另外，依賴函式寫成 `def` 或 `async def` 會有不同的執行方式，這個之後再談，現在先知道有這回事就好。

## 這封信帶走的東西

重複出現在多支路由、位於處理邏輯之前的那段程式碼，抽成一個函式，用 `Depends` 接回來。需要別的依賴，就在這個函式的簽章上再宣告一次。

下一封要看的是，如果依賴裡面丟出例外，例如查不到使用者，使用者最後看到的會是什麼。

回信告訴我，你現在的框架裡是怎麼共用請求前那段邏輯的，是中介層、攔截器，還是複製貼上。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

---

## 待實測項目

以下項目目前只查到官方文件說法，我無法執行程式碼，請在 Python 3.12 與目前 FastAPI 版本實測後再發信：

1. 兩段完整程式碼（`pagination` 範例與 `get_user` / `get_quota` 範例）需實際啟動，用 `curl` 或 `/docs` 確認可執行。第二段範例的 `x_user` 標頭須送出 `X-User`，缺少時應回 422。
2. 「同一請求內 `get_user` 只呼叫一次」：官方子依賴文件有明確敘述，但規格書要求實測。建議在 `get_user` 裡加 `print`，送一次請求確認只印一行，再試 `use_cache=False` 確認印兩行。
3. `Header()` 的底線轉連字號行為（`x_user` 對應 `X-User`）依官方標頭參數文件為預設，本次未查證，需實測。
4. `Annotated` 需 FastAPI 0.95.0 以上，官方文件建議升到 0.95.1 以上；本系列鎖定的版本需確認符合。
5. 範例路由用 `def`，官方範例多用 `async def`，兩者行為差異留待第 7 期，發信前確認範例用 `def` 無問題。
6. 稿中「`Annotated` 寫法優先、舊式寫法位於次要位置」的措辭，依官方 dependencies 頁面現況，發信當天需再看一次頁面。
