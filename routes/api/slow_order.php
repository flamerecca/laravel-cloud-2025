<?php

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::post('/slow-order', function (Request $request) {
    $url = 'https://laravelslowapi-main-j3y6l3.laravel.cloud/api/';

    [$userCount, $orderCount] = Concurrency::run([
        fn () => DB::table('users')->count(),
        fn () => DB::table('orders')->count(),
    ]);

    $responses = Http::pool(fn (Pool $pool) => [
        $pool->as('user')->withQueryParameters([
            'user' => $request->get('user'),
        ])->get($url.'user-check'),
        $pool->as('exchange')->withQueryParameters([
            'usd' => $request->get('usd'),
        ])->get($url.'exchange-rate'),
        $pool->as('date')->withQueryParameters([
            'date' => $request->get('date'),
        ])->get($url.'date-check'),
    ]);

    return [
        'userChecked' => $responses['user']->object()->response,
        'twd' => $responses['exchange']->object()->twd,
        'dateChecked' => $responses['date']->object()->isChecked,
    ];
});
