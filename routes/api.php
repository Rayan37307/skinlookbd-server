<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Cart\CartController;
use App\Http\Controllers\Api\V1\Cart\CouponController;
use App\Http\Controllers\Api\V1\Catalog\BannerController;
use App\Http\Controllers\Api\V1\Catalog\BrandController;
use App\Http\Controllers\Api\V1\Catalog\CategoryController;
use App\Http\Controllers\Api\V1\Catalog\ConcernController;
use App\Http\Controllers\Api\V1\Catalog\MenuController;
use App\Http\Controllers\Api\V1\Catalog\ProductController;
use App\Http\Controllers\Api\V1\Catalog\ProductReviewController;
use App\Http\Controllers\Api\V1\Catalog\SiteMediaController;
use App\Http\Controllers\Api\V1\Checkout\CheckoutController;
use App\Http\Controllers\Api\V1\Me\AddressController;
use App\Http\Controllers\Api\V1\Me\ProfileController;
use App\Http\Controllers\Api\V1\Orders\OrderController;
use App\Http\Controllers\Api\V1\Payments\CodPaymentController;
use App\Http\Controllers\Api\V1\Shipping\ShippingEstimateController;
use App\Http\Controllers\Api\V1\Wishlist\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('brands', [BrandController::class, 'index'])->name('brands.index');
    Route::get('concerns', [ConcernController::class, 'index'])->name('concerns.index');
    Route::get('menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('banners', [BannerController::class, 'index'])->name('banners.index');
    Route::get('site-media', [SiteMediaController::class, 'index'])->name('site-media.index');

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('{slug}', [ProductController::class, 'show'])->name('show');
        Route::get('{product}/reviews', [ProductReviewController::class, 'index'])->name('reviews.index');
        Route::post('{product}/reviews', [ProductReviewController::class, 'store'])
            ->middleware('auth:sanctum')
            ->name('reviews.store');
    });

    Route::prefix('auth')->name('auth.')->group(function () {
        Route::middleware('throttle:auth')->group(function () {
            Route::post('register', [AuthController::class, 'register'])->name('register');
            Route::post('login', [AuthController::class, 'login'])->name('login');
            Route::post('forgot-password', [PasswordResetController::class, 'forgot'])->name('forgot-password');
            Route::post('reset-password', [PasswordResetController::class, 'reset'])->name('reset-password');
        });

        Route::middleware('throttle:otp')->group(function () {
            Route::post('otp/send', [OtpController::class, 'send'])->name('otp.send');
            Route::post('otp/verify', [OtpController::class, 'verify'])->name('otp.verify');
        });

        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
    });

    Route::middleware('auth:sanctum')->prefix('me')->name('me.')->group(function () {
        Route::get('/', [ProfileController::class, 'show'])->name('show');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');

        Route::apiResource('addresses', AddressController::class)->except(['show']);
    });

    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'show'])->name('show');
        Route::post('items', [CartController::class, 'store'])->name('items.store');
        Route::patch('items', [CartController::class, 'update'])->name('items.update');
        Route::delete('items', [CartController::class, 'destroy'])->name('items.destroy');
    });

    Route::post('coupons/validate', [CouponController::class, 'validateCoupon'])
        ->middleware('auth:sanctum')
        ->name('coupons.validate');

    Route::get('shipping/estimate', ShippingEstimateController::class)->name('shipping.estimate');

    // Works for both guests (X-Cart-Token) and authenticated customers (Sanctum bearer
    // token) — deliberately not behind auth:sanctum, same pattern as the cart routes above.
    Route::post('checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:checkout')
        ->name('checkout');

    // Public, guest-friendly order lookup by order number + recipient phone. Must be
    // registered before the auth-gated `orders/{order}` route below so "track" doesn't
    // get swallowed by that implicit-binding segment.
    Route::get('orders/track', [OrderController::class, 'track'])
        ->middleware('throttle:track')
        ->name('orders.track');

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('{order}', [OrderController::class, 'show'])->name('show');
            Route::post('{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');
        });

        Route::post('payments/cod/confirm', [CodPaymentController::class, 'confirm'])
            ->middleware('role:super-admin|order-manager')
            ->name('payments.cod.confirm');

        Route::prefix('wishlist')->name('wishlist.')->group(function () {
            Route::get('/', [WishlistController::class, 'index'])->name('index');
            Route::post('/', [WishlistController::class, 'store'])->name('store');
            Route::delete('{product}', [WishlistController::class, 'destroy'])->name('destroy');
        });
    });

    Route::middleware('auth:sanctum')->prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));
});
