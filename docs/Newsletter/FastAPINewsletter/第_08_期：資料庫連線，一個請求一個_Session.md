---
category: FastAPI 基礎教學
status: draft
summary: 引擎全域建一個，Session 每個請求建一個，用 yield 依賴管好開與關，再看迴圈裡逐筆查作者造成的 N+1。
tags:
  - FastAPI
  - Python
  - SQLModel
  - Session
  - N+1
---

# 資料庫連線，一個請求一個 Session

我聽過一個很典型的故事：服務上線第一天一切正常，流量一上來，資料庫突然回報連線數滿了。翻程式碼，發現某支路由裡自己 `create_engine` 了一次，用完沒關，每個請求都多開一組連線池。

另一種版本更隱晦：全域建了一個 Session，所有請求共用。單人測試時沒事，兩個人同時送出請求，一個人的 `commit` 把另一個人做到一半的變更一起寫了進去。

這兩種錯是同一個問題的兩面，也有同一個答案：引擎全域一個，Session 每個請求一個。

## 引擎只建一次

引擎負責管連線池，整個程式只該有一個，放在模組層級就好。Session 則是一次工作單元，記錄你載入了哪些物件、改了什麼，請求結束就該丟掉。

這封信用同步引擎。原因很直接：SQLModel 預設搭配的 `Session` 是同步的，路由寫成 `def`，FastAPI 會把它丟進執行緒池跑，阻塞的資料庫呼叫不會卡住事件迴圈。非同步引擎要另外搭配驅動程式與 `AsyncSession`，這個之後再開一個主題談。

資料表先定義兩張，書和作者：

```python
class Author(SQLModel, table=True):
    id: int | None = Field(default=None, primary_key=True)
    name: str
    books: list["Book"] = Relationship(back_populates="author")

class Book(SQLModel, table=True):
    id: int | None = Field(default=None, primary_key=True)
    title: str
    author_id: int | None = Field(default=None, foreign_key="author.id")
    author: Author | None = Relationship(back_populates="books")
```

`table=True` 這個參數告訴 SQLModel，這個類別要對應到真正的資料表，不只是一個 Pydantic 模型。

## 用 yield 依賴提供 Session

FastAPI 的 `Depends` 可以在請求進來時呼叫一個函式，把結果注入路由，簽章上宣告需要什麼，框架負責準備。如果依賴函式用 `yield`，`yield` 之前的程式碼在請求處理前執行，之後的在回應送出後執行，剛好拿來管 Session 的開與關：

```python
engine = create_engine(
    "sqlite:///database.db",
    connect_args={"check_same_thread": False},
)

def get_session():
    with Session(engine) as session:
        yield session

SessionDep = Annotated[Session, Depends(get_session)]
```

`check_same_thread` 只有 SQLite 需要。官方文件的說法是，一個請求可能用到不只一個執行緒，例如依賴函式與路由各跑在不同執行緒，所以要放行。換成 PostgreSQL 或 MySQL 就不用這個參數。

路由只要在簽章上寫 `SessionDep`，就拿到屬於這個請求的 Session：

```python
@app.post("/books")
def create_book(book: Book, session: SessionDep) -> Book:
    session.add(book)
    session.commit()
    session.refresh(book)
    return book

@app.get("/books/{book_id}")
def read_book(book_id: int, session: SessionDep):
    return session.get(Book, book_id)
```

新增是 `add`、`commit`、`refresh` 三步，用主鍵查詢則是一行 `session.get`。這支路由沒有任何一處手動開關連線，離開 `with` 區塊時，Session 自己會關。

這套做法有個正式名稱：一個請求一個 Session，由 `yield` 依賴負責生命週期。

## FastAPI 不會替你擋 N+1

Session 管好之後，另一類問題才浮上來。假設要列出所有書和作者名字：

```python
@app.get("/books")
def list_books(session: SessionDep):
    books = session.exec(select(Book)).all()
    return [
        {"title": b.title, "author": b.author.name}
        for b in books
    ]
```

看起來很乾淨，但 `b.author` 預設是用到才去查。100 本書，就是 1 次查書，加上迴圈裡 100 次逐筆查作者，共 101 次查詢。這個問題叫 N+1，FastAPI 與 SQLModel 都不會警告你，本機資料少時甚至感覺不到。

修正方式是在查詢時就告訴它一起載入。SQLModel 的 `select` 底層是 SQLAlchemy，載入選項直接用 SQLAlchemy 的：

```python
from sqlalchemy.orm import selectinload

stmt = select(Book).options(selectinload(Book.author))
books = session.exec(stmt).all()
```

這樣只會多一次批次查詢，總共 2 次，與書的數量無關。想親眼確認，可以在 `create_engine` 加上 `echo=True`，看終端機印出幾條 `SELECT`。

## 這封信帶走的東西

引擎全域一個，Session 用 `yield` 依賴每個請求建一個。列出關聯資料時，先想一下迴圈裡有沒有隱形查詢，有就在 `select` 上加載入選項。

另外提醒，範例若用 `SQLModel.metadata.create_all(engine)` 建表，只適合示範，正式環境要用遷移工具管理結構變更。

資料能存了，下一個問題是：怎麼知道是誰在存？下一封談登入之後，每個請求怎麼證明自己是誰。

回信告訴我，你現在用的是哪一套 ORM，有沒有被 N+1 咬過。如果這封信是朋友轉給你的，歡迎訂閱，每週一封。

---

## 待實測項目

以下項目我無法執行程式碼，請在 Python 3.12 與本系列鎖定的版本實測後再發信：

1. 確認鎖定的 SQLModel、SQLAlchemy、FastAPI 版本號。稿中未寫任何版本號。
2. 所有程式碼需完整啟動實測，含 `import` 與 `app = FastAPI()`。稿中省略 `from sqlmodel import ...`、`from typing import Annotated`、`from fastapi import Depends`。
3. `select(Book).options(selectinload(Book.author))` 在目前 SQLModel 版本是否可執行，型別檢查工具是否報錯。官方 SQLModel 文件的關聯頁面未提及任何載入選項，此寫法只來自 SQLAlchemy 慣例，必須實測。若不順，改用 SQLAlchemy 建議的寫法並在稿中註明。
4. 「100 本書 101 次查詢、改後 2 次」是依 lazy loading 與 `selectinload` 行為推算，需開 `echo=True` 實際數 `SELECT` 條數。多對一關聯用 `joinedload` 是否比 `selectinload` 更合適，也順便比較。
5. `list_books` 的 N+1 版本回傳 `b.author.name`，若某本書的 `author_id` 為空會拋 `AttributeError`，範例需準備資料確認，或加防護。
6. `read_book` 查不到時回傳 `None`，實際回應為 `null`；官方範例改丟 404，是否在本期補一行需決定。
7. 「SQLModel 預設 Session 為同步」與「`def` 路由在執行緒池執行」依官方文件說法撰寫，建議對照第 7 期定案措辭。
8. `check_same_thread` 說明依官方文件，需確認稿中措辭與文件現況一致。
9. 同一份稿中「共用單一全域 Session 會讓 `commit` 混入他人變更」的情境，屬推論，建議用兩個並行請求實測，否則改為較保守的說法。
10. 「忘了關連線導致連線數爆掉」的開場故事為概括情境，發信前確認是否保留或改為自己的經驗。
11. 規格書信封主旨候選與預覽文字未納入本稿，發信時另行處理。
12. `create_all` 的使用與遷移工具的後續待辦，請列入後續主題清單。
