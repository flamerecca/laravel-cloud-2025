<?php

use App\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;

Route::post('/newsletter', NewsletterController::class)
    ->middleware('throttle:6,1')
    ->name('newsletter.subscribe');
