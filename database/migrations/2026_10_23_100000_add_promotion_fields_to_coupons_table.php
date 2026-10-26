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
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('name')->nullable()->after('code');
            $table->string('method', 20)->default('code')->after('name');
            $table->string('code')->nullable()->change();
            $table->unsignedInteger('max_discount')->nullable()->after('discount_value');
            $table->boolean('first_order_only')->default(false)->after('min_order_value');
            $table->boolean('allow_combination')->default(false)->after('max_uses_per_user');
            $table->enum('discount_type', ['percent', 'flat', 'free_delivery'])->change();
        });

        // Give existing coupons a display name so the edit form is valid without manual edits.
        DB::table('coupons')->whereNull('name')->update(['name' => DB::raw('code')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('coupons')
            ->where('discount_type', 'free_delivery')
            ->update(['discount_type' => 'flat', 'discount_value' => 0]);

        foreach (DB::table('coupons')->whereNull('code')->pluck('id') as $id) {
            DB::table('coupons')->where('id', $id)->update(['code' => 'AUTO-'.$id]);
        }

        Schema::table('coupons', function (Blueprint $table) {
            $table->enum('discount_type', ['percent', 'flat'])->change();
            $table->string('code')->change();
            $table->dropColumn([
                'name',
                'method',
                'max_discount',
                'first_order_only',
                'allow_combination',
            ]);
        });
    }
};
