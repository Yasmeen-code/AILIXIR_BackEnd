<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Cloudinary\Cloudinary;

Route::get('/upload-test', function () {
    return view('upload-test');
});
