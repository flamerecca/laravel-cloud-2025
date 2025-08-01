## Tic Tac Toe 遊戲

### 專案目標

建立一個互動式的 Tic Tac Toe（井字遊戲）頁面，讓使用者可以與電腦對戰。

### 頁面需求

#### 遊戲主頁面

* 顯示一個 3x3 的遊戲版面，每個格子都是可點擊的。
* 顯示目前的遊戲狀態，例如：「輪到你了」、「電腦思考中」、「你贏了！」、「電腦獲勝！」或「平手！」。
* 遊戲結束後，提供一個「重新開始」的按鈕，讓使用者可以重置遊戲版面並開始新的一局。

### 遊戲規則與流程

| 項目   | 規則                                    |
|------|---------------------------------------|
| 角色分配 | 使用者固定為 'X'，電腦為 'O'。                   |
| 先後順序 | 使用者先手。                                |
| 下棋規則 | 使用者點擊空白格子後，該格子會顯示 'X'。                |
| 電腦回合 | 使用者下完棋後，電腦會自動在一個隨機的空白格子下 'O'。         |
| 勝利條件 | 任一方（使用者或電腦）在水平、垂直或對角線上連成三個相同的棋子。      |
| 平手條件 | 所有 9 個格子都下滿棋，但無人獲勝。                   |
| 遊戲結束 | 當有勝利者或平手時，遊戲結束，版面無法再點擊，直到使用者按下「重新開始」。 |

### 實作建議結構

#### Livewire Component：`TicTacToe.php`

| 類型 | 名稱                   | 描述                                             |
|----|----------------------|------------------------------------------------|
| 屬性 | `$board`             | `array`，儲存 3x3 遊戲版面的狀態，例如 `['', '', '', ...]`。 |
| 屬性 | `$winner`            | `string`，記錄勝利者 ('X' 或 'O') 或 'draw' (平手)。      |
| 屬性 | `$isGameOver`        | `bool`，標記遊戲是否已結束。                              |
| 方法 | `playerMove($index)` | 處理使用者的點擊，更新版面，並觸發電腦回合。                         |
| 方法 | `computerMove()`     | 電腦隨機選擇一個空格下棋。                                  |
| 方法 | `resetGame()`        | 重置所有屬性，開始新遊戲。                                  |
| 方法 | `checkWinner()`      | 每次下棋後檢查是否有勝利者或平手。                              |

#### Blade Template：`tic-tac-toe.blade.php`

* 使用 `@foreach` 迴圈渲染 `$board` 陣列，建立 3x3 的版面。
* 每個格子都是一個按鈕或可點擊的 `div`，並綁定 `wire:click="playerMove({{ $index }})"`。
* 使用 `@if` 條件式顯示遊戲狀態訊息（例如：輪到誰、勝利者、平手）。
* 顯示「重新開始」按鈕，並綁定 `wire:click="resetGame()"`。
