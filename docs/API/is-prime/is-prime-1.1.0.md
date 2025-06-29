## 判斷是否為質數 API 規格文件 1.1.0

### 概述

`判斷是否為質數 API 1.1.0` 提供比起 [判斷是否為質數 API 規格文件 1.0.0](is-prime-1.0.0.md) 更穩定的服務，加上整合測試與單元測試，以避免後續修改時產生錯誤

### 調整內容

#### 整合測試

加上以下整合測試內容：

透過 API 輸入數字為 `91` 時，回傳

```json
{
    "number": 91,
    "isPrime": false
}
```

#### 單元測試

首先，將判斷是否為質數的部分移動到 `isPrimeService` 內的 `__invoke()`

```php
public function __invoke(int $number): bool
{
}
```
並 加上以下單元測試內容：

- 輸入數字為 `7` 時，回傳 `true`
- 輸入數字為 `42` 時，回傳 `false`
- 輸入數字為 `2` 時，回傳 `true`
