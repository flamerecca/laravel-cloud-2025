<?php

use App\Http\Controllers\N8nOrderController;
use App\Http\Controllers\N8nOrderHeaderController;
use App\Http\Controllers\QuoteController;

Route::get('/n8n-order', N8nOrderController::class);

Route::get('/n8n-order-header', N8nOrderHeaderController::class);

Route::get('/n8n-quote', QuoteController::class);
