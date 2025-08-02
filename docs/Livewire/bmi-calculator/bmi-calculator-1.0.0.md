# BMI Calculator v1.0.0

## 功能

- 根據使用者輸入的身高和體重計算身體質量指數 (BMI)。
- 能夠在公制單位（公分/公斤）和英制單位（英寸/磅）之間切換。
- 即時顯示計算出的 BMI 值。
- 根據計算出的 BMI 值顯示對應的體重狀況（例如：體重過輕、正常範圍、過重、肥胖）。

## 版面

- 兩個選項按鈕 (radio button) 用於選擇單位系統：
    - 公制 (Metric)
    - 英制 (Imperial)
- 一個數字輸入框，用於輸入身高。輸入框旁的標籤會根據所選單位動態變更為「公分」或「英寸」。
- 一個數字輸入框，用於輸入體重。輸入框旁的標籤會根據所選單位動態變更為「公斤」或「磅」。
- 一個區塊，用於顯示計算後的 BMI 結果，格式為 `您的 BMI 值為: XX.X`。
- 一個區塊，用於顯示體重狀況，例如 `狀況: 正常範圍`。
- 計算是即時的，因此不需要「計算」按鈕。

## 行為

### Public Properties

```php
class BmiCalculator extends Component
{
    public string $unit = 'metric';
    public ?float $height = null;
    public ?float $weight = null;

    // Computed Property
    public float $bmi;
    public string $status;
}
```

### Computed Properties

- `getBmiProperty()`:
    - 這是一個計算屬性，當 `unit`, `height`, 或 `weight` 任何一個屬性變更時，它會自動重新計算。
    - 首先檢查 `height` 和 `weight` 是否為有效且大於 0 的數字。如果不是，返回 0。
    - 如果 `unit` 是 `metric`，使用公式 `體重(公斤) / (身高(公尺))^2` 進行計算。注意：輸入的身高是公分，需要轉換為公尺。
    - 如果 `unit` 是 `imperial`，使用公式 `(體重(磅) / (身高(英寸))^2) * 703` 進行計算。
    - 返回計算結果，保留一位小數。

- `getStatusProperty()`:
    - 這是一個計算屬性，依賴於 `$this->bmi` 的值。
    - 根據 BMI 值返回對應的健康狀況字串：
        - BMI < 18.5: "體重過輕"
        - 18.5 <= BMI < 24: "正常範圍"
        - 24 <= BMI < 27: "過重"
        - 27 <= BMI < 30: "輕度肥胖"
        - 30 <= BMI < 35: "中度肥胖"
        - BMI >= 35: "重度肥胖"

### 使用者互動

- **單位選擇**: 使用者點擊單位選項按鈕時，`$unit` 屬性會透過 `wire:model.live` 更新，並即時觸發重新計算。
- **輸入身高/體重**: 使用者在輸入框中輸入時，`$height` 和 `$weight` 屬性會透過 `wire:model.live` 更新，並即時觸發重新計算。
- **結果顯示**: `$bmi` 和 `$status` 的計算結果會自動顯示在頁面上。

## 參數 (驗證)

- `unit`:
    - `required`
    - `string`
    - `in:metric,imperial`
- `height`:
    - `required`
    - `numeric`
    - `min:1`
- `weight`:
    - `required`
    - `numeric`
    - `min:1`
