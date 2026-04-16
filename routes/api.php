<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Company\BranchController;
use App\Http\Controllers\Company\CompanyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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

Route::middleware(['auth:api'])->group(function () {
    Route::post('companies', [CompanyController::class, 'store'])->middleware('role_or_permission:create companies')->name('companies.store');
    Route::put('companies/{companyId}', [CompanyController::class, 'update'])->middleware('role_or_permission:update companies')->name('companies.update');
    Route::get('companies', [CompanyController::class, 'index'])->middleware('role_or_permission:view companies')->name('companies.index');
    Route::get('companies/{companyId}', [CompanyController::class, 'show'])->middleware('role_or_permission:view companies')->name('companies.show');
});

Route::middleware(['auth:api'])->group(function () {

    Route::prefix('companies/{companyId}')->group(function () {
        Route::post('/branches', [BranchController::class, 'store'])
            ->middleware('permission:create branches');

        Route::put('/branches/{branchId}', [BranchController::class, 'update'])
            ->middleware('permission:update branches');

        Route::get('/branches', [BranchController::class, 'index'])
            ->middleware('permission:view branches');

        Route::get('/branches/{branchId}', [BranchController::class, 'show'])
            ->middleware('permission:view branches');

    });
});
