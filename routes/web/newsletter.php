<?php

use App\Enums\NewsletterTopic;
use App\Http\Controllers\NewsletterConfirmationController;
use App\Http\Controllers\NewsletterController;
use Illuminate\Support\Facades\Route;

// Registered before newsletter.show so "confirm" is not bound as a topic.
Route::get('/newsletter/confirm', NewsletterConfirmationController::class)
    ->middleware('throttle:6,1')
    ->name('newsletter.confirm');

Route::get('/newsletter/{topic}', fn (NewsletterTopic $topic) => view('newsletter.show', ['topic' => $topic]))
    ->name('newsletter.show');

Route::post('/newsletter', NewsletterController::class)
    ->middleware('throttle:6,1')
    ->name('newsletter.subscribe');
