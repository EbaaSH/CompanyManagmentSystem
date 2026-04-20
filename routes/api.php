<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\CompanyManager\BranchController;
use App\Http\Controllers\PlatformQuery\BranchQueryController;
use App\Http\Controllers\PlatformQuery\CompanyQueryController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
$user = auth()->user();

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login']);
    Route::delete('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api');
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'otp',
], function ($router) {
    Route::post('/verify', [OtpController::class, 'verify'])->middleware('auth:api');
    Route::post('/resend', [OtpController::class, 'resendCode'])->middleware('auth:api');
});
Route::middleware(['auth:api'])
    ->group(function () {

        Route::prefix('companies')->group(function () {

            Route::post('/', [CompanyController::class, 'store']);
            Route::put('/{id}', [CompanyController::class, 'update']);

            Route::get('/', [CompanyQueryController::class, 'index']);
            Route::get('/{id}', [CompanyQueryController::class, 'show']);

        });
    });

Route::middleware(['auth:api'])
    ->group(function () {

        Route::prefix('branches')->group(function () {

            Route::post('/', [BranchController::class, 'store']);
            Route::put('/{id}', [BranchController::class, 'update']);

            Route::get('/', [BranchQueryController::class, 'index']);
            Route::get('/{id}', [BranchQueryController::class, 'show']);

        });
    });
