<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Allows guest checkout: an Order can now exist with no owning user (user_id) and no
     * saved Address record (address_id) — guests submit shipping details inline instead,
     * already captured by the existing recipient_name/shipping_* snapshot columns. A
     * CouponUsage can likewise have no user_id when a guest redeems a coupon.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['address_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('address_id')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('address_id')->references('id')->on('addresses')->restrictOnDelete();
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['address_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->unsignedBigInteger('address_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('address_id')->references('id')->on('addresses')->restrictOnDelete();
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
