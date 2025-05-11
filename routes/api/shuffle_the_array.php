<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

Route::post('/shuffle', function (Request $request) {
    // 直接驗證，失敗自動丟 exception + 422 response
    try {
        $validated = $request->validate([
            'nums' => ['required', 'array'],
            'nums.*' => ['integer'],
            'n' => ['required', 'integer'],
        ]);
        $nums = $validated['nums'];
        $n = $validated['n'];

        // 自訂驗證 (長度)
        if (count($nums) != 2 * $n) {
            return response()->json([
                'errors' => ['nums' => ['The nums array length must be exactly 2 * n.']]
            ], 422);
        }
    } catch (ValidationException $e) {
        return response()->json([
            'errors' => $e->errors(),
        ], 422);
    }

    $nums = $request->input('nums');
    $n = $request->input('n');
    $x = array_slice($nums, 0, $n);
    $y = array_slice($nums, $n, $n);
    $result = [];
    for ($i = 0; $i < $n; $i++) {
        $result[] = $x[$i];
        $result[] = $y[$i];
    }
    return response()->json([
        'result' => $result,
    ]);
});
