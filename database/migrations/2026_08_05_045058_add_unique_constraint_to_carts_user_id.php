<?php

use App\Models\Cart;
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
        // Without a unique constraint, concurrent requests (e.g. two open tabs) could each
        // find no existing cart for a user and both create one, leaving duplicate cart rows.
        // Which row a later request resolves to is then non-deterministic, so a cart item that
        // was visible a moment ago can 404 on removal. Merge any duplicates before adding the
        // constraint so the migration doesn't fail on data that already violates it.
        $duplicateUserIds = DB::table('carts')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('count(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $carts = Cart::where('user_id', $userId)->orderBy('id')->get();
            $primary = $carts->shift();

            foreach ($carts as $duplicate) {
                foreach ($duplicate->items as $item) {
                    $existing = $primary->items()->where('product_variant_id', $item->product_variant_id)->first();

                    if ($existing) {
                        $existing->increment('quantity', $item->quantity);
                    } else {
                        $primary->items()->create([
                            'product_variant_id' => $item->product_variant_id,
                            'quantity' => $item->quantity,
                        ]);
                    }
                }

                $duplicate->delete();
            }
        }

        Schema::table('carts', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });
    }
};
