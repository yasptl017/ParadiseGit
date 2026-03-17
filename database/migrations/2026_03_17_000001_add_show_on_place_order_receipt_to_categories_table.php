<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('categories') || Schema::hasColumn('categories', 'show_on_place_order_receipt')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_on_place_order_receipt')->default(true)->after('receipt_sort_order');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('categories') || !Schema::hasColumn('categories', 'show_on_place_order_receipt')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('show_on_place_order_receipt');
        });
    }
};
