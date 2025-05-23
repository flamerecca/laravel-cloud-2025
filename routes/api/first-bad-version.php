<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

Route::get('/first-bad-version', function () {
    $low = 1;
    $high = 2 ** 20;

    while ($low < $high) {
        $mid = floor(($low + $high) / 2);
        $response = Http::get('https://laravelslowapi-main-j3y6l3.laravel.cloud/api/is-bad-version', [
            'version' => $mid,
        ]);

        if ($response->json('bad-version') === true) {
            $high = $mid;
        } else {
            $low = $mid + 1;
        }
    }


    return response()->json(['version' => $low]);
});
