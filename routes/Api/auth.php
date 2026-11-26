<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvitationController;
use App\Http\Middleware\EmailLinkExpired;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\PasswordResetLinkExpired;
use Illuminate\Support\Facades\Route;

// Route::prefix('v1')->group(function () {

Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware(['throttle:10,1']);

Route::get('/auth/verify/{token}', [InvitationController::class, 'accountverify'])
    ->middleware(EmailLinkExpired::class)
    ->name('verify-account');

Route::post('/forgetpassword', [InvitationController::class, 'forgetpassword'])
    ->middleware('throttle:5,1');


Route::post('/forgetpassword/{token}', [AuthController::class, 'forgetpassword'])
    ->middleware(PasswordResetLinkExpired::class);

Route::middleware('auth.token')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

});
// });