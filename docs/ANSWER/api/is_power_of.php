<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/is-power-of-two', function (Request $request) {
    $validated = $request->validate([
        'number' => 'required|integer',
    ]);
    $number = (int)$validated['number'];
    if ($number == 0) {
        return response()->json([
            'number' => $number,
            'isPowerOfTwo' => false
        ]);
    }
    return response()->json([
        'number' => $number,
        'isPowerOfTwo' => ($number & ($number - 1)) == 0
    ]);
});

Route::get('/is-power-of-three', function (Request $request) {
    $validated = $request->validate([
        'number' => 'required|integer',
    ]);
    $number = (int)$validated['number'];
    if ($number == 0) {
        return response()->json([
            'number' => $number,
            'isPowerOfThree' => false
        ]);
    }
    return response()->json([
        'number' => $number,
        'isPowerOfThree' => 1162261467 % $number == 0
    ]);
});

Route::get('/is-power-of-four', function (Request $request) {
    $validated = $request->validate([
        'number' => 'required|integer',
    ]);
    $number = (int)$validated['number'];
    if ($number == 0) {
        return response()->json([
            'number' => $number,
            'isPowerOfFour' => false
        ]);
    }
    $power = log($number, 4.0);
    return response()->json([
        'number' => $number,
        'isPowerOfFour' => floor($power) == ceil($power)
    ]);
});
