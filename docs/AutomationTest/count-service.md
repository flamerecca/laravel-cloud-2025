## CountService add Test

### add()

針對 [App/Services/CountService](/app/Services/CountService.php) 裡面 `add()`

```php
public function add(int $a, int $b = 1): int
{
    return $a + $b;
}
```

新增自動化測試以驗證 `add()` 的行為

案例：

- 正常相加：`2 + 3 = 5`
- 包含零：`3 + 0 = 3`
- 包含負數：`3 + -3 = 0`
- b 的預設值：`2 + null = 3`

### divide()

針對 [App/Services/CountService](/app/Services/CountService.php) 裡面 `divide()`

```php
public function divide(int $a, int $b = 1): float|int
{
    return $a / $b;
}
```

新增自動化測試以驗證 `divide()` 的行為

案例：

- 正常相除：`6 / 3` 等於 `2`
- 小數：`7 / 3` 等於 `2`
- 被除數為零：`0 / 3` 等於 `0`
- 除數為零：`3 / 0` 拋出例外 `DivisionByZeroError`
- 包含負數：`3 + -3 = 0`
