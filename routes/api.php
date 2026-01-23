<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AwardController;
use App\Http\Controllers\ScientistController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\ResearcherController;
use App\Http\Controllers\UserController;
use Cloudinary\Cloudinary;



//awards
Route::get('/awards', [AwardController::class, 'index']);
Route::get('/awards/{id}', [AwardController::class, 'show']);

//scientists
Route::get('/scientists', [ScientistController::class, 'index']);
Route::get('/scientists/{id}', [ScientistController::class, 'show']);

//user
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

//cloudinary file upload test route
Route::post('/upload-file', function (Request $request) {

    $request->validate([
        'file' => 'required|file'
    ]);

    try {
        $file = $request->file('file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();

        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => ['secure' => true]
        ]);

        $result = $cloudinary->uploadApi()->upload(
            $file->getRealPath(),
            [
                'resource_type' => 'raw',
                'public_id' => $originalName,
                'filename_override' => $originalName . '.' . $extension
            ]
        );

        return response()->json([
            'url' => $result['secure_url']
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ], 500);
    }
});
