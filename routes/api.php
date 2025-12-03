<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\ScientistController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResearcherController;
use App\Http\Controllers\UserController;


//awards
Route::get('/awards', [AwardController::class, 'index']);
Route::get('/awards/{id}', [AwardController::class, 'show']);

//scientists
Route::get('/scientists', [ScientistController::class, 'index']);
Route::get('/scientists/{id}', [ScientistController::class, 'show']);


Route::prefix('user')->group(function () {
    Route::post('register', [UserController::class, 'register']);
    Route::post('verify-email', [UserController::class, 'verifyEmail']);
    Route::post('login', [UserController::class, 'login']);
    Route::post('forgot-password', [UserController::class, 'sendForgotPasswordOtp']);
    Route::post('reset-password', [UserController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::post('update-profile', [UserController::class, 'updateProfile']);
        Route::post('logout', [UserController::class, 'logout']);
    });
});
