<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\ScientistController;
//awards
Route::get('/awards', [AwardController::class, 'index']);
Route::get('/awards/{id}', [AwardController::class, 'show']);

//scientists
Route::get('/scientists', [ScientistController::class, 'index']);
Route::get('/scientists/{id}', [ScientistController::class, 'show']);
