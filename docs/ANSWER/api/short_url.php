<?php

use App\Http\Controllers\ShortUrlController;
use Illuminate\Support\Facades\Route;

Route::apiResource('short-urls', ShortUrlController::class);
