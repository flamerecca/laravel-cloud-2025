<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/valid-parentheses', function (Request $request) {
    $request->validate([
        'input' => ['bail', 'required', 'string', 'max:1000', 'regex:/^[\(\)\{\}\[\]]*$/'],
    ], [
        'input.required' => "The 'input' field is required.",
        'input.string' => "The 'input' field must be a string.",
        'input.max' => "The 'input' field must not exceed 1000 characters.",
        'input.regex' => "The 'input' field must only contain (), {}, [].",
    ]);

    $input = $request->input('input');

    $stack = [];
    $map = [
        ')' => '(',
        '}' => '{',
        ']' => '[',
    ];

    $isValid = true;
    for ($i = 0; $i < strlen($input); $i++) {
        $char = $input[$i];

        if (in_array($char, ['(', '{', '['])) {
            $stack[] = $char;
        } elseif (isset($map[$char])) {
            if (empty($stack) || array_pop($stack) !== $map[$char]) {
                $isValid = false;
                break;
            }
        } else {
            $isValid = false;
            break;
        }
    }

    if (!empty($stack)) {
        $isValid = false;
    }

    return response()->json([
        'input' => $input,
        'valid' => $isValid,
    ]);
});
