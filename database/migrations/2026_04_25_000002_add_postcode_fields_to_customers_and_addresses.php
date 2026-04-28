<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('address_distance');
            }
        });

        Schema::table('addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('addresses', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('address');
            }
        });

        Schema::table('order_addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('order_addresses', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_addresses', function (Blueprint $table) {
            if (Schema::hasColumn('order_addresses', 'postal_code')) {
                $table->dropColumn('postal_code');
            }
        });

        Schema::table('addresses', function (Blueprint $table) {
            if (Schema::hasColumn('addresses', 'postal_code')) {
                $table->dropColumn('postal_code');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'postal_code')) {
                $table->dropColumn('postal_code');
            }
        });
    }
};
