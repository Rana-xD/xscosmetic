<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLateOvertimeToClockInOutTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('clock_in_out', function (Blueprint $table) {
            $table->integer('late_minutes')->default(0)->after('total_hours');
            $table->integer('overtime_minutes')->default(0)->after('late_minutes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('clock_in_out', function (Blueprint $table) {
            $table->dropColumn(['late_minutes', 'overtime_minutes']);
        });
    }
}
