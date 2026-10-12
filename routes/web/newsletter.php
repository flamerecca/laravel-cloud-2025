<?php

use App\Enums\NewsletterTopic;
use App\Http\Controllers\NewsletterConfirmationController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NewsletterUnsubscribeController;
use Illuminate\Support\Facades\Route;

Route::get('/newsletter', fn () => view('newsletter.index', ['topics' => NewsletterTopic::cases()]))
    ->name('newsletter.index');

// Registered before newsletter.show so "confirm" is not bound as a topic.
Route::get('/newsletter/confirm', NewsletterConfirmationController::class)
    ->middleware('throttle:6,1')
    ->name('newsletter.confirm');

// GET only asks for confirmation: mail scanners prefetch links, so writing must take a POST.
Route::get('/newsletter/unsubscribe', [NewsletterUnsubscribeController::class, 'show'])
    ->middleware('throttle:6,1')
    ->name('newsletter.unsubscribe');

// Also the target of the List-Unsubscribe-Post one-click request, which carries no CSRF token.
Route::post('/newsletter/unsubscribe', [NewsletterUnsubscribeController::class, 'store'])
    ->middleware('throttle:6,1');

Route::get('/newsletter/{topic}', fn (NewsletterTopic $topic) => view('newsletter.show', ['topic' => $topic]))
    ->name('newsletter.show');

Route::post('/newsletter', NewsletterController::class)
    ->middleware('throttle:6,1')
    ->name('newsletter.subscribe');
