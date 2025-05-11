<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Pool;

Route::get('/slow-order', function (Request $request) {
    $url = 'https://laravelslowapi-main-j3y6l3.laravel.cloud/api/';

    $responses = Http::pool(fn (Pool $pool) => [
        $pool->as('user')->withQueryParameters([
            'user' => 'alice',
        ])->get($url . 'user-check'),
        $pool->as('exchange')->withQueryParameters([
            'usd' => '10',
        ])->get($url . 'exchange-rate'),
        $pool->as('date')->withQueryParameters([
            'date' => '2025-05-02',
        ])->get($url . 'date-check'),
    ]);

    return [
        'user' => $responses['user']->object()->response,
        'twd' => $responses['exchange']->object()->twd,
        'date' => $responses['date']->object()->isChecked,
    ];
});
