## Password Generator v1.0.0

### 功能

- 能夠生成一個隨機密碼。
- 能夠自訂密碼的長度。
- 能夠選擇是否包含大寫字母、小寫字母、數字和特殊符號。
- 能夠複製生成的密碼到剪貼簿。

### 版面

- 一個滑桿 (slider) 用於選擇密碼長度，範圍為 8 到 32。
- 四個核取方塊 (checkbox) 分別用於選擇是否包含：
    - 大寫字母 (A-Z)
    - 小寫字母 (a-z)
    - 數字 (0-9)
    - 特殊符號 (!@#$%^&*)
- 一個「生成密碼」按鈕。
- 一個唯讀的文字輸入框，用於顯示生成的密碼。
- 一個「複製」按鈕，位於密碼顯示框旁邊。

### 行為

#### Public Properties

```php
class PasswordGenerator extends Component
{
    public int $length = 12;
    public bool $includeUppercase = true;
    public bool $includeLowercase = true;
    public bool $includeNumbers = true;
    public bool $includeSymbols = false;
    public string $password = '';
}
```

#### Methods

- `mount()`: 元件掛載時，會自動生成一組初始密碼。
- `generate()`:
    - 當使用者點擊「生成密碼」按鈕時觸發 (`wire:click="generate"`)。
    - 根據使用者選擇的條件（長度、字元類型）生成一個新的隨機密碼。
    - 更新 `$password` 屬性，版面會自動重新渲染以顯示新密碼。
    - 如果沒有選擇任何字元類型，則 `$password` 會顯示錯誤訊息「請至少選擇一種字元類型」。

#### 使用者互動

- **長度調整**: 使用者拖動滑桿時，`$length` 屬性會透過 `wire:model` 雙向綁定。
- **字元類型選擇**: 使用者勾選或取消勾選核取方塊時，對應的 `includeUppercase`, `includeLowercase`, `includeNumbers`, `includeSymbols` 屬性會透過 `wire:model` 更新。
- **複製功能**:
    - 「複製」按鈕使用 Alpine.js 的 `$clipboard` 功能。
    - 按鈕範例：`<button @click="$clipboard(password)">複製</button>`
    - Livewire 需要將 `$password` 傳遞給前端 Alpine.js。

### 參數

- `length`:
    - `required`
    - `integer`
    - `min:8`
    - `max:32`
- `includeUppercase`:
    - `required`
    - `boolean`
- `includeLowercase`:
    - `required`
    - `boolean`
- `includeNumbers`:
    - `required`
    - `boolean`
- `includeSymbols`:
    - `required`
    - `boolean`
- **自訂驗證**: 必須至少選擇一種字元類型。
