<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

Route::get('/three-divisors', function (Request $request) {
    $n = $request->query('n');

    if (!is_numeric($n) || (int)$n <= 0) {
        return response()->json([
            'error' => "'n' must be an integer.",
        ], status: Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    if ($n < 2) {
        return response()->json([
            'n' => (int)$n,
            'isThree' => false,
        ]);
    }

    $square = sqrt($n);
    $isThree = floor($square) === ceil($square);

    return response()->json([
        'n' => (int)$n,
        'isThree' => $isThree,
    ]);
});
