<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWeekdayWeekendClockTimesToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->time('weekday_clock_in')->nullable()->after('default_clock_out');
            $table->time('weekday_clock_out')->nullable()->after('weekday_clock_in');
            $table->time('weekend_clock_in')->nullable()->after('weekday_clock_out');
            $table->time('weekend_clock_out')->nullable()->after('weekend_clock_in');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['weekday_clock_in', 'weekday_clock_out', 'weekend_clock_in', 'weekend_clock_out']);
        });
    }
}
