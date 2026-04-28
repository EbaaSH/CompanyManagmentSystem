<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\BranchManager\MenuController;
use App\Http\Controllers\CompanyManager\BranchController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Driver\DeliveryController;
use App\Http\Controllers\Employee\OrderController as EmployeeOrderController;
use App\Http\Controllers\PlatformQuery\BranchQueryController;
use App\Http\Controllers\PlatformQuery\CompanyQueryController;
use App\Http\Controllers\PlatformQuery\CustomerQueryController;
use App\Http\Controllers\PlatformQuery\DeliveryQueryController;
use App\Http\Controllers\PlatformQuery\DriverQueryController;
use App\Http\Controllers\PlatformQuery\EmployeeQueryController;
use App\Http\Controllers\PlatformQuery\MenuQueryController;
use App\Http\Controllers\PlatformQuery\OrderQueryController;
use App\Http\Controllers\SuperAdmin\CompanyController;
use App\Http\Middleware\TwoFactorMiddleware;
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
    Route::delete('/logout', [AuthController::class, 'logout'])->middleware(['auth:api', TwoFactorMiddleware::class]);
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware(['auth:api', TwoFactorMiddleware::class]);
    Route::get('/me', [AuthController::class, 'me'])->middleware(['auth:api', TwoFactorMiddleware::class]);
});

Route::group([
    'middleware' => 'api',
    'prefix' => 'otp',
], function ($router) {
    Route::post('/verify', [OtpController::class, 'verify'])->middleware('auth:api');
    Route::post('/resend', [OtpController::class, 'resendCode'])->middleware('auth:api');
});
Route::middleware(['auth:api', TwoFactorMiddleware::class])
    ->group(function () {

        Route::prefix('profile')->group(function () {

            Route::put('/update', [ProfileController::class, 'updateProfile']);
            Route::put('/update-password', [ProfileController::class, 'updatePassword']);

        });
    });

Route::middleware(['auth:api', TwoFactorMiddleware::class])
    ->group(function () {

        Route::prefix('companies')->group(function () {

            Route::post('/', [CompanyController::class, 'store']);
            Route::put('/{id}', [CompanyController::class, 'update']);

            Route::delete('/{id}', [CompanyController::class, 'delete']);
            Route::put('/{id}/restore', [CompanyController::class, 'restore']);

            Route::get('/', [CompanyQueryController::class, 'index']);
            Route::get('/{id}', [CompanyQueryController::class, 'show']);

        });
    });

Route::middleware(['auth:api', TwoFactorMiddleware::class])
    ->group(function () {
        Route::prefix('branches')->group(function () {
            Route::post('/', [BranchController::class, 'store']);
            Route::put('/{id}', [BranchController::class, 'update']);

            Route::delete('/{id}', [BranchController::class, 'delete']);
            Route::put('/{id}/restore', [BranchController::class, 'restore']);

            Route::get('/', [BranchQueryController::class, 'index']);
            Route::get('/{id}', [BranchQueryController::class, 'show']);
        });
    });
Route::middleware(['auth:api', TwoFactorMiddleware::class])
    ->group(function () {
        Route::prefix('employees')->group(function () {
            Route::get('/', [EmployeeQueryController::class, 'index']);
            Route::get('/{id}', [EmployeeQueryController::class, 'show']);
        });
    });

Route::middleware(['auth:api', TwoFactorMiddleware::class])
    ->group(function () {
        Route::prefix('drivers')->group(function () {
            Route::get('/', [DriverQueryController::class, 'index']);
            Route::get('/{id}', [DriverQueryController::class, 'show']);
        });
    });

Route::middleware(['auth:api', TwoFactorMiddleware::class])
    ->group(function () {
        Route::prefix('menus')->group(function () {
            Route::post('/', [MenuController::class, 'store']);
            Route::put('/{id}', [MenuController::class, 'update']);

            Route::delete('/{id}', [MenuController::class, 'delete']);
            Route::put('/{id}/restore', [MenuController::class, 'restore']);

            Route::get('/', [MenuQueryController::class, 'index']);
            Route::get('/{id}', [MenuQueryController::class, 'show']);
        });
    });

// Customer
Route::post('customers/register', [CustomerController::class, 'registerCustomer']);
Route::middleware(['auth:api', TwoFactorMiddleware::class])
    ->group(function () {
        Route::prefix('customers')->group(function () {
            Route::put('/updateProfile/{id}', [CustomerController::class, 'updateCustomer']);

            Route::get('/', [CustomerQueryController::class, 'index']);
            Route::get('/{id}', [CustomerQueryController::class, 'show']);

        });
    });

// ===== CUSTOMER ORDER ROUTES =====
Route::middleware(['auth:api', TwoFactorMiddleware::class])->group(function () {
    Route::prefix('customer/orders')->group(function () {
        // Place new order
        Route::post('/', [CustomerOrderController::class, 'store']);
        // Update pending order
        Route::put('/{id}', [CustomerOrderController::class, 'update']);
        // Cancel order
        Route::delete('/{id}', [CustomerOrderController::class, 'cancel']);
    });
});

// ===== EMPLOYEE ORDER MANAGEMENT ROUTES =====
Route::middleware(['auth:api', TwoFactorMiddleware::class])->group(function () {
    Route::prefix('employee/orders')->group(function () {
        Route::patch('/{id}/mark-preparing', [EmployeeOrderController::class, 'markPreparing']);
        // Mark as ready for pickup
        Route::patch('/{id}/mark-ready', [EmployeeOrderController::class, 'markReady']);
        // Reject pending order
        Route::patch('/{id}/reject', [EmployeeOrderController::class, 'reject']);
    });
});

// ===== LEGACY QUERY ROUTES (Keep for backward compatibility) =====
Route::middleware(['auth:api', TwoFactorMiddleware::class])
    ->group(function () {
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderQueryController::class, 'index']);
            Route::get('/{id}', [OrderQueryController::class, 'show']);
        });
    });


// ===== DRIVER DELIVERY ROUTES =====
Route::middleware(['auth:api', TwoFactorMiddleware::class])->group(function () {
    Route::prefix('driver/deliveries')->group(function () {
        // Accept delivery
        Route::patch('/{id}/accept', [DeliveryController::class, 'accept']);
        // Reject delivery
        Route::patch('/{id}/reject', [DeliveryController::class, 'reject']);
        // Pickup order from branch
        Route::patch('/{id}/pickup', [DeliveryController::class, 'pickup']);
        // Deliver order to customer
        Route::patch('/{id}/deliver', [DeliveryController::class, 'deliver']);
        // Mark delivery as failed
        Route::patch('/{id}/fail', [DeliveryController::class, 'fail']);
    });
});

Route::middleware(['auth:api', TwoFactorMiddleware::class])
    ->group(function () {
        Route::prefix('deliveries')->group(function () {
            Route::get('/', [DeliveryQueryController::class, 'index']);
            Route::get('/{id}', [DeliveryQueryController::class, 'show']);
        });
    });

