<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/is-prime', function (Request $request) {
    $number = $request->query('number');

    // 1. 嚴謹的輸入驗證
    if (! is_numeric($number) || intval($number) != $number || $number < 1) {
        return response()->json([
            'error' => "Missing or invalid 'number' parameter.",
        ], 422);
    }

    $number = (int) $number;

    // 2. 質數判斷函數
    $isPrime = function (int $n): bool {
        if ($n <= 1) {
            return false;
        }
        if ($n === 2) {
            return true;
        }
        if ($n % 2 === 0) {
            return false;
        }

        $max = (int) sqrt($n);
        for ($i = 3; $i <= $max; $i += 2) {
            if ($n % $i === 0) {
                return false;
            }
        }

        return true;
    };

    return response()->json([
        'number' => $number,
        'isPrime' => $isPrime($number),
    ]);
});
