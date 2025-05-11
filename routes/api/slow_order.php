<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Pool;

Route::get('/slow-order', function (Request $request) {
    return [];
});
