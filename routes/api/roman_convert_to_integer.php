<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/roman-convert-to-integer', function (Request $request) {
    $romanMap = [
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

    $roman = strtoupper($request->query('roman', ''));

    if (empty($roman)) {
        return response()->json([
            'error' => 'Parameter "roman" is required.'
        ], 400);
    }

    $isValidRoman = preg_match('/^M{0,3}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$/', $roman) === 1;
    if (!$isValidRoman) {
        return response()->json([
            'error' => 'Invalid Roman numeral format or out of range'
        ], 400);
    }

    $i = 0;
    $result = 0;
    $length = strlen($roman);

    while ($i < $length) {
        if ($i + 1 < $length && isset($romanMap[substr($roman, $i, 2)])) {
            $result += $romanMap[substr($roman, $i, 2)];
            $i += 2;
        } else {
            $result += $romanMap[$roman[$i]];
            $i += 1;
        }
    }

    if ($result < 1 || $result > 3999) {
        return response()->json([
            'error' => 'Roman numeral is out of supported range (1 ~ 3999)'
        ], 400);
    }

    return response()->json([
        'integer' => $result
    ]);
});
