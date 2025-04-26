<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::post('/shuffle', function (Request $request) {
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
