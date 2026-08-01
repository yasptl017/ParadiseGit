<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('coupons', 'first_time_basis')) {
            Schema::table('coupons', function (Blueprint $table) {
                // none | new_phone | new_email | new_phone_and_email | new_phone_or_email
                $table->string('first_time_basis')->default('none');
            });
        }

        if (!Schema::hasColumn('coupons', 'order_types')) {
            Schema::table('coupons', function (Blueprint $table) {
                // JSON array of: dine_in, pickup, delivery. Null/empty = all order types.
                $table->text('order_types')->nullable();
            });
        }

        if (!Schema::hasColumn('coupons', 'auto_apply')) {
            Schema::table('coupons', function (Blueprint $table) {
                // When true the discount applies automatically, no code entry needed.
                $table->boolean('auto_apply')->default(false);
            });
        }
    }

    public function down()
    {
        foreach (['first_time_basis', 'order_types', 'auto_apply'] as $column) {
            if (Schema::hasColumn('coupons', $column)) {
                Schema::table('coupons', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
