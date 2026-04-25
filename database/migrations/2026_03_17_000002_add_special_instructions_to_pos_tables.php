<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('p_o_s_tables', function (Blueprint $table) {
            $table->text('special_instructions')->nullable()->after('resolved_order');
        });
    }

    public function down()
    {
        Schema::table('p_o_s_tables', function (Blueprint $table) {
            $table->dropColumn('special_instructions');
        });
    }
};
