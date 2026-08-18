<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Kept as restrictOnDelete (matching the FK it replaces on `products.category_id`)
            // so a category still can't be deleted while any product is assigned to it —
            // see the "category with products cannot be deleted" behavior in CategoryController.
            $table->foreignId('category_id')->constrained()->restrictOnDelete();

            $table->primary(['product_id', 'category_id']);
        });

        // Carry every product's existing single category over into the new pivot table before
        // the column disappears below — nothing should lose its category in this migration.
        DB::table('products')
            ->whereNotNull('category_id')
            ->select('id', 'category_id')
            ->orderBy('id')
            ->chunkById(500, function ($products) {
                $rows = $products->map(fn ($product) => [
                    'product_id' => $product->id,
                    'category_id' => $product->category_id,
                ])->all();

                if ($rows !== []) {
                    DB::table('category_product')->insert($rows);
                }
            });

        Schema::table('products', function (Blueprint $table) {
            // MySQL refuses to drop the index while the FK still relies on it to enforce the
            // constraint, so the FK has to go first, even though it reads a little backwards.
            $table->dropForeign(['category_id']);
            $table->dropIndex(['category_id', 'status']);
            $table->dropColumn('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        // Best-effort: a product may have picked up more than one category while this was live,
        // so this restores just its lowest-id category rather than losing the column entirely.
        DB::table('category_product')
            ->select('product_id', DB::raw('MIN(category_id) as category_id'))
            ->groupBy('product_id')
            ->orderBy('product_id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('products')->where('id', $row->product_id)->update(['category_id' => $row->category_id]);
                }
            });

        Schema::table('products', function (Blueprint $table) {
            $table->index(['category_id', 'status']);
        });

        Schema::dropIfExists('category_product');
    }
};
