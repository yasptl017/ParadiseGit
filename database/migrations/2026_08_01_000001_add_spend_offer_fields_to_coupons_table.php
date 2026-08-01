<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('coupons', 'offer_kind')) {
            Schema::table('coupons', function (Blueprint $table) {
                // coupon | buy_get_discount | buy_get_free_product
                $table->string('offer_kind')->default('coupon');
            });
        }

        if (!Schema::hasColumn('coupons', 'max_discount')) {
            Schema::table('coupons', function (Blueprint $table) {
                // Caps the money value of a percentage discount. Null/0 = uncapped.
                $table->double('max_discount')->nullable();
            });
        }

        if (!Schema::hasColumn('coupons', 'gift_product_id')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->unsignedBigInteger('gift_product_id')->nullable();
            });
        }

        if (!Schema::hasColumn('coupons', 'gift_qty')) {
            Schema::table('coupons', function (Blueprint $table) {
                $table->integer('gift_qty')->default(1);
            });
        }
    }

    public function down()
    {
        foreach (['offer_kind', 'max_discount', 'gift_product_id', 'gift_qty'] as $column) {
            if (Schema::hasColumn('coupons', $column)) {
                Schema::table('coupons', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
