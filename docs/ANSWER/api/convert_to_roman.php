<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/convert-to-roman', function (Request $request) {
    $validated = $request->validate([
        'number' => 'required|integer',
    ]);

    $number = $validated['number'];
    $map = [
        'M' => 1000,
        'CM' => 900,
        'D' => 500,
        'CD' => 400,
        'C' => 100,
        'XC' => 90,
        'L' => 50,
        'XL' => 40,
        'X' => 10,
        'IX' => 9,
        'V' => 5,
        'IV' => 4,
        'I' => 1,
    ];

    $result = '';
    foreach ($map as $roman => $value) {
        while ($number >= $value) {
            $result .= $roman;
            $number -= $value;
        }
    }

    return [
        'roman' => $result
    ];
});
