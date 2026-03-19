<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AwardController;
use App\Http\Controllers\Api\ScientistController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\SimulationController;
use Cloudinary\Cloudinary;

// ==================== AWARDS ====================
Route::get('/awards', [AwardController::class, 'index']);
Route::get('/awards/{id}', [AwardController::class, 'show']);
Route::get('/awards/{id}/scientists', [AwardController::class, 'getScientistsByAward']);


// ==================== SCIENTISTS ====================
Route::get('/scientists', [ScientistController::class, 'index']);
Route::get('/scientists/{id}', [ScientistController::class, 'show']);
Route::get('/scientists/{id}/awards', [ScientistController::class, 'getAwardsByScientist']);


// ==================== USERS & AUTHENTICATION ====================
Route::prefix('user')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // email verification
    Route::post('verify-email', [EmailVerificationController::class, 'verifyEmail']);
    Route::post('/resend-otp', [EmailVerificationController::class, 'resendOtp']);

    // password reset
    Route::post('forgot-password', [PasswordResetController::class, 'sendForgotPasswordOtp']);
    Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);
    Route::post('/resend-reset-password-otp', [PasswordResetController::class, 'resendResetPasswordOtp']);

    // login google
    Route::post('auth/google', [AuthController::class, 'handleGoogleCallback']);

    // profile and logout routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::post('update-profile', [UserController::class, 'updateProfile']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});


// ====================NEWS====================
// Public routes
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/refresh', [NewsController::class, 'refresh']);
Route::get('/news/clear', [NewsController::class, 'clear']);
Route::get('/news/categories', [NewsController::class, 'getCategories']);
Route::post('/news/{articleId}/share', [NewsController::class, 'shareArticle']);


// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/news/{articleId}/save', [NewsController::class, 'saveArticle']);
    Route::get('/news/saved', [NewsController::class, 'getSavedArticles']);
    Route::delete('/news/saved/{savedArticleId}', [NewsController::class, 'unsaveArticle']);
});


// ==================== AI JOBS ====================
Route::middleware('auth:sanctum')->prefix('ai')->group(function () {
    Route::post('/run', [AiController::class, 'run']);
    Route::get('/status/{job:job_id}', [AiController::class, 'status']);
    Route::get('/preview/{job:job_id}', [AiController::class, 'preview']);
    Route::get('/download/top/{job:job_id}', [AiController::class, 'downloadTop']);
    Route::get('/download/full/{job:job_id}', [AiController::class, 'downloadFull']);
    Route::get('/history', [AiController::class, 'history']);
});


// ==================== SIMULATIONS ====================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/simulation/run', [SimulationController::class, 'run']);
    Route::get('/simulation/{id}/status', [SimulationController::class, 'status']);
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
