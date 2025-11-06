<?php

use App\Http\Controllers\NewsletterController;

Route::post('/newsletter', NewsletterController::class)->name('newsletter.subscribe');
