<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SellerPromoController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/csrf-cookie', function (Request $request) {
    return response()->json(['csrf_token' => csrf_token()]);
});

Route::get('/sanctum/csrf-cookie', function (Request $request) {
    return response()->json(['csrf_token' => csrf_token()]);
});

Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::prefix('api')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

    Route::middleware('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/dashboard', DashboardController::class);

        Route::middleware('role:super_admin,admin,promo_manager,reports_manager')->group(function () {
            Route::get('/customers', [CustomerController::class, 'index']);
            Route::get('/branches', [BranchController::class, 'index']);
            Route::get('/promo-codes', [PromoCodeController::class, 'index']);
            Route::get('/users', [UserController::class, 'index']);
        });

        Route::middleware('role:super_admin,admin,promo_manager')->group(function () {
            Route::apiResource('customers', CustomerController::class)->only(['store', 'update', 'destroy']);
            Route::patch('/customers/{customer}/toggle', [CustomerController::class, 'toggle']);
            Route::post('/customers/import', [CustomerController::class, 'import']);
            Route::get('/customers-template', [CustomerController::class, 'template']);

            Route::apiResource('branches', BranchController::class)->only(['store', 'update', 'destroy']);
            Route::patch('/branches/{branch}/toggle', [BranchController::class, 'toggle']);
            Route::apiResource('promo-codes', PromoCodeController::class)
                ->parameters(['promo-codes' => 'promoCode'])
                ->only(['store', 'update', 'destroy']);
            Route::patch('/promo-codes/{promoCode}/toggle', [PromoCodeController::class, 'toggle']);
            Route::get('/promo-codes-generate', [PromoCodeController::class, 'generate']);
            Route::post('/users', [UserController::class, 'store'])->middleware('role:super_admin');
            Route::put('/users/{user}', [UserController::class, 'update'])->middleware('role:super_admin');
            Route::patch('/users/{user}/toggle', [UserController::class, 'toggle'])->middleware('role:super_admin');
            Route::patch('/users/{user}/password', [UserController::class, 'changePassword'])->middleware('role:super_admin');
        });

        Route::middleware('role:seller')->group(function () {
            Route::post('/seller/validate-promo', [SellerPromoController::class, 'validatePromo']);
            Route::post('/seller/redeem-promo', [SellerPromoController::class, 'redeem']);
            Route::get('/seller/redemptions', [SellerPromoController::class, 'myRedemptions']);
        });

        Route::middleware('role:super_admin,admin,promo_manager,reports_manager')->group(function () {
            Route::get('/reports/redemptions', [ReportController::class, 'index']);
            Route::get('/reports/redemptions/export', [ReportController::class, 'export']);
        });
    });
});

Route::view('/{any?}', 'app')->where('any', '.*');
