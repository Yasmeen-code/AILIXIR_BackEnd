<?php

use Illuminate\Support\Facades\Route;
// use Illuminate\Http\Request;
use Cloudinary\Cloudinary;
// use App\Http\Controllers\NewsController;
use App\Http\Controllers\Api\AwardController;
use App\Http\Controllers\Api\ScientistController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\ApiDocsController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/upload-test', function () {
    return view('upload-test');
});

// Route::get('/news', [NewsController::class, 'list']);
// Route::get('/news/refresh', [NewsController::class, 'refresh']);
// Route::get('/news-page', function () {
//     return view('news');
// });


// Route::get('/news', [NewsController::class, 'index'])->name('news.index');
// Route::get('/news/refresh', [NewsController::class, 'refresh'])->name('news.refresh');

Route::get('/api-endpoints', [ApiDocsController::class, 'index']);
