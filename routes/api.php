<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\ScientistController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ResearcherController;

//awards
Route::get('/awards', [AwardController::class, 'index']);
Route::get('/awards/{id}', [AwardController::class, 'show']);

//scientists
Route::get('/scientists', [ScientistController::class, 'index']);
Route::get('/scientists/{id}', [ScientistController::class, 'show']);

//auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
//researcher
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/researcher/update-profile', [ResearcherController::class, 'updateProfile']);
    Route::get('/researcher/profile', [ResearcherController::class, 'getFullProfile']);
});
