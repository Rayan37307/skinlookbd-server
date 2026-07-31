<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Same change as addresses (see replace_area_with_email_on_addresses) — orders
     * snapshot the shipping details at checkout time, so this column needs to move too.
     * Destructive for any existing shipping_area data on real orders.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_area');
            $table->string('shipping_email')->nullable()->after('recipient_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_email');
            $table->string('shipping_area')->nullable()->after('shipping_city');
        });
    }
};
