<?php

use App\Http\Controllers\IngredientController;
use Illuminate\Support\Facades\Route;

Route::apiResource('ingredients', IngredientController::class);
