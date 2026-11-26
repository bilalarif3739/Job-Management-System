<?php

use App\Http\Controllers\OpenRouterModelController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.token')->group(function () {
    Route::get('/models', [OpenRouterModelController::class, 'index']);
});
