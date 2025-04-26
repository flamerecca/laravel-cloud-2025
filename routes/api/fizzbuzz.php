<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

Route::get('/fizzbuzz', function (Request $request) {
    try {
        $validated = $request->validate([
            'start' => ['bail', 'required', 'integer'],
            'end' => ['required', 'integer', 'gte:start'],
        ]);
    } catch (ValidationException $exception) {
        return response()->json(
            [
                'error' => "Invalid input. 'start' and 'end' must be integers, and start <= end."
            ],
            status: Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }


    $start = $validated['start'];
    $end = $validated['end'];
    $answer = [];
    for ($i = $start; $i <= $end; $i++) {
        $answer[] = match (true) {
            $i % 15 == 0 => 'FizzBuzz',
            $i % 3 == 0 => 'Fizz',
            $i % 5 == 0 => 'Buzz',
            default => (string)$i,
        };
    }
    return response()->json(
        [
            'start' => (int)$start,
            'end' => (int)$end,
            'result' => $answer
        ]
    );
});

Route::post('/v2/fizzbuzz', function (Request $request) {
    $validated = $request->validate([
        'start' => ['required', 'integer'],
        'end' => ['required', 'integer', 'gte:start'],
        'rules' => ['required', 'array'],
    ]);

    $start = $validated['start'];
    $end = $validated['end'];
    $rules = $validated['rules'];

    $result = [];

    for ($i = $start; $i <= $end; $i++) {
        $output = '';

        foreach ($rules as $divisor => $word) {
            if ($i % (int)$divisor === 0) {
                $output .= $word;
            }
        }

        $result[] = $output !== '' ? $output : (string)$i;
    }

    return response()->json([
        'start' => (int)$start,
        'end' => (int)$end,
        'rules' => $rules,
        'result' => $result
    ]);
});

