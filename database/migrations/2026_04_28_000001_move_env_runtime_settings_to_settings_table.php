<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'default_discount')) {
                $table->decimal('default_discount', 10, 2)->default(0)->after('agent_port');
            }

            if (!Schema::hasColumn('settings', 'default_delivery')) {
                $table->decimal('default_delivery', 10, 2)->default(0)->after('default_discount');
            }

            if (!Schema::hasColumn('settings', 'maximum_distance')) {
                $table->decimal('maximum_distance', 10, 2)->default(0)->after('default_delivery');
            }

            if (!Schema::hasColumn('settings', 'minimum_order_amount')) {
                $table->decimal('minimum_order_amount', 10, 2)->default(40)->after('maximum_distance');
            }

            if (!Schema::hasColumn('settings', 'order_delete_password')) {
                $table->text('order_delete_password')->nullable()->after('minimum_order_amount');
            }

            if (!Schema::hasColumn('settings', 'print_agent_key')) {
                $table->text('print_agent_key')->nullable()->after('order_delete_password');
            }
        });

        $setting = \App\Models\Setting::first();

        if ($setting) {
            $setting->default_discount = env('DEFAULTS_DISCOUNT', $setting->default_discount ?? 0);
            $setting->default_delivery = env('DEFAULTS_DELIVERY', $setting->default_delivery ?? 0);
            $setting->maximum_distance = env('MAXIMUM_DISTANCE', $setting->maximum_distance ?? 0);
            $setting->minimum_order_amount = env('MINIMUM_AMOUNT', $setting->minimum_order_amount ?? 40);

            if (empty($setting->order_delete_password) && env('ORDER_DELETE_PASSWORD')) {
                $setting->order_delete_password = env('ORDER_DELETE_PASSWORD');
            }

            if (empty($setting->print_agent_key) && env('PRINT_AGENT_KEY')) {
                $setting->print_agent_key = env('PRINT_AGENT_KEY');
            }

            $setting->save();
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $dropColumns = [];

            foreach ([
                'default_discount',
                'default_delivery',
                'maximum_distance',
                'minimum_order_amount',
                'order_delete_password',
                'print_agent_key',
            ] as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
