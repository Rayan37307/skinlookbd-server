<?php

use App\Http\Controllers\Api\V1\Admin\BannerController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\CouponController;
use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\InventoryAdjustmentController;
use App\Http\Controllers\Api\V1\Admin\InventoryController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\ProductImageController;
use App\Http\Controllers\Api\V1\Admin\ProductVariantController;
use App\Http\Controllers\Api\V1\Admin\ReportController;
use App\Http\Controllers\Api\V1\Admin\ReviewController;
use App\Http\Controllers\Api\V1\Admin\SiteMediaController;
use App\Http\Controllers\Api\V1\Admin\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:super-admin|catalog-manager')->group(function () {
    Route::apiResource('categories', CategoryController::class)->except(['show']);

    Route::apiResource('products', ProductController::class);
    Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
    Route::patch('variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
    Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy');
    Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');

    Route::post('inventory/adjustments', [InventoryAdjustmentController::class, 'store'])->name('inventory.adjustments.store');
    Route::get('inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');

    Route::apiResource('coupons', CouponController::class)->except(['show']);
    Route::apiResource('banners', BannerController::class)->except(['show']);

    Route::prefix('site-media')->name('site-media.')->group(function () {
        Route::put('{key}', [SiteMediaController::class, 'update'])
            ->whereIn('key', array_keys(config('site_media.slots')))
            ->name('update');
        Route::delete('{key}/image', [SiteMediaController::class, 'destroyImage'])
            ->whereIn('key', array_keys(config('site_media.slots')))
            ->name('destroy-image');
    });

    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::patch('{review}/status', [ReviewController::class, 'updateStatus'])->name('status');
    });
});

Route::middleware('role:super-admin|order-manager')->group(function () {
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::patch('{order}/status', [OrderController::class, 'updateStatus'])->name('status');
        Route::post('{order}/refund', [OrderController::class, 'refund'])->name('refund');
    });

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('summary', [DashboardController::class, 'summary'])->name('summary');
        Route::get('sales', [DashboardController::class, 'sales'])->name('sales');
        Route::get('top-products', [DashboardController::class, 'topProducts'])->name('top-products');
    });

    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('{customer}', [CustomerController::class, 'show'])->name('show');
    });

    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
});

Route::middleware('role:super-admin')->group(function () {
    Route::apiResource('staff', StaffController::class)->except(['show']);
});
