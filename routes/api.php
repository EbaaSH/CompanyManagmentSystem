<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\BranchManager\MenuController;
use App\Http\Controllers\CompanyManager\BranchController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\PlatformQuery\BranchQueryController;
use App\Http\Controllers\PlatformQuery\CompanyQueryController;
use App\Http\Controllers\PlatformQuery\CustomerQueryController;
use App\Http\Controllers\PlatformQuery\DriverQueryController;
use App\Http\Controllers\PlatformQuery\EmployeeQueryController;
use App\Http\Controllers\PlatformQuery\MenuQueryController;
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
Route::middleware(['auth:api'])
    ->group(function () {
        Route::prefix('employees')->group(function () {
            Route::get('/', [EmployeeQueryController::class, 'index']);
            Route::get('/{id}', [EmployeeQueryController::class, 'show']);
        });
    });

Route::middleware(['auth:api'])
    ->group(function () {
        Route::prefix('drivers')->group(function () {
            Route::get('/', [DriverQueryController::class, 'index']);
            Route::get('/{id}', [DriverQueryController::class, 'show']);
        });
    });

Route::middleware(['auth:api'])
    ->group(function () {
        Route::prefix('menus')->group(function () {
            Route::post('/', [MenuController::class, 'store']);
            Route::put('/{id}', [MenuController::class, 'update']);

            Route::get('/', [MenuQueryController::class, 'index']);
            Route::get('/{id}', [MenuQueryController::class, 'show']);
        });
    });

Route::post('customers/register', [CustomerController::class, 'registerCustomer']);

Route::middleware(['auth:api'])
    ->group(function () {
        Route::prefix('customers')->group(function () {
            Route::put('/updateProfile/{id}', [CustomerController::class, 'updateCustomer']);
            Route::put('/updatePassword/{id}', [CustomerController::class, 'updatePassword']);

        });
    });
Route::middleware(['auth:api'])
    ->group(function () {
        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomerQueryController::class, 'index']);
            Route::get('/{id}', [CustomerQueryController::class, 'show']);
        });
    });
