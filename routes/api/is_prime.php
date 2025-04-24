<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/is-prime', function (Request $request) {
    $number = $request->query('number');

    if (!is_numeric($number) || intval($number) != $number || $number < 1) {
        return response()->json([
            'error' => "Missing or invalid 'number' parameter."
        ], 422);
    }

    $number = (int) $number;
    $isPrime = true;
    if ($number <= 1) {$isPrime = false;}
    if ($number === 2) {$isPrime = true;}
    if ($number % 2 === 0) {$isPrime = false;}

    for ($i = 3; $i <= sqrt($number); $i += 2) {
        if ($number % $i === 0) {$isPrime = false;}
    }

    return response()->json([
        'number' => $number,
        'isPrime' => $isPrime
    ]);
});
