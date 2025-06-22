<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

Route::get('/gcd', function (Request $request) {
    try {
        $validated = $request->validate([
            'a' => ['bail', 'required', 'integer', 'gt:0'],
            'b' => ['required', 'integer', 'gt:0'],
        ]);
    } catch (ValidationException $exception) {
        return response()->json(
            [
                'error' => "Invalid input. Parameters a and b must be positive integers."
            ],
            status: Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }


    $a = $validated['a'];
    $b = $validated['b'];

    // 計算 GCD
    function gcd($x, $y)
    {
        while ($y != 0) {
            $temp = $y;
            $y = $x % $y;
            $x = $temp;
        }
        return $x;
    }

    $gcd = gcd((int)$a, (int)$b);

    return response()->json([
        'a' => (int)$a,
        'b' => (int)$b,
        'gcd' => $gcd,
    ]);
});
