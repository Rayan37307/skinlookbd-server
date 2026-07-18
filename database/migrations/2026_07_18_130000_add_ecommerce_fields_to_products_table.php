<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->unique()->after('slug');
            $table->unsignedInteger('sale_price')->nullable()->after('base_price');
            $table->unsignedInteger('cost_price')->nullable()->after('sale_price');
            $table->boolean('track_inventory')->default(true)->after('status');
            $table->unsignedInteger('stock_quantity')->default(0)->after('track_inventory');
            $table->text('short_description')->nullable()->after('description');
            $table->json('additional_information')->nullable()->after('ingredients');
            $table->boolean('free_shipping')->default(false)->after('stock_quantity');
            $table->string('meta_title')->nullable()->after('free_shipping');
            $table->string('meta_description')->nullable()->after('meta_title');
            $table->string('focus_keyword')->nullable()->after('meta_description');
            $table->string('canonical_url')->nullable()->after('focus_keyword');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'sku',
                'sale_price',
                'cost_price',
                'track_inventory',
                'stock_quantity',
                'short_description',
                'additional_information',
                'free_shipping',
                'meta_title',
                'meta_description',
                'focus_keyword',
                'canonical_url',
            ]);
        });
    }
};
