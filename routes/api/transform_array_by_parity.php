<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/transform-array-by-parity', function (Request $request) {
    // 驗證輸入
    $validated = $request->validate([
        'nums' => ['required', 'array'],
        'nums.*' => 'integer',
    ]);

    $nums = $validated['nums'];

    // 依規則轉換
    $transformed = array_map(function ($num) {
        return $num % 2 === 0 ? 0 : 1;
    }, $nums);

    sort($transformed);

    return response()->json([
        'result' => $transformed
    ]);
});
