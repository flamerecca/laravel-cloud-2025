<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

Route::get('/leap-year', function (Request $request) {
    try {
        $validated = $request->validate([
            'year' => 'required|integer|',
        ]);
    } catch (ValidationException $exception) {
        return response()->json(
            [
                'error' => "Missing or invalid 'year' parameter."
            ],
            status: Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }


    $year = $validated['year'];
    return response()->json(
        [
            'year' => (int)$year,
            'isLeapYear' => $year % 4 == 0,
        ]
    );
});
