<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The frontend's address form dropped the free-text "area" field in favour of a
     * fixed city dropdown (see ShippingService::validCities()) plus an optional contact
     * email. Dropping `area` here is destructive for any existing data in that column —
     * confirm there's nothing worth preserving before running this against a database
     * with real customer addresses.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('area');
            $table->string('email')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('email');
            $table->string('area')->nullable()->after('city');
        });
    }
};
