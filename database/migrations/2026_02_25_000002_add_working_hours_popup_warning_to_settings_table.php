<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'working_hours_popup_warning')) {
                $table->text('working_hours_popup_warning')->nullable()->after('appointment_bg');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'working_hours_popup_warning')) {
                $table->dropColumn('working_hours_popup_warning');
            }
        });
    }
};
