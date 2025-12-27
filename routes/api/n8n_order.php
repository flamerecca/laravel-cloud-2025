<?php

use App\Http\Controllers\N8nOrderController;
use App\Http\Controllers\N8nOrderHeaderController;

Route::get('/n8n-order', N8nOrderController::class);

Route::get('/n8n-order-header', N8nOrderHeaderController::class);
