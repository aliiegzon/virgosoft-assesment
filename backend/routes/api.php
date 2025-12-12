<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/refresh', [AuthController::class, 'refreshToken']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');

Route::get('/google/redirect', [GoogleLoginController::class, 'redirectToGoogle'])->name('google.redirect')->middleware('web');
Route::get('/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('google.callback');

Route::post('/password/email', [PasswordController::class, 'sendEmail']);
Route::post('/password/reset', [PasswordController::class, 'resetPassword']);
Route::post('/password/set', [PasswordController::class, 'setPassword']);
Route::get('/password/validate-token', [PasswordController::class, 'validateToken']);

Route::middleware('auth:api')->group(function () {

    Route::apiResource('/users', UserController::class);

    Route::post('/users/resend-invitation/{user}', [UserController::class, 'resendInvitation']);

    Route::get('/profile', [ProfileController::class, 'show']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
});
