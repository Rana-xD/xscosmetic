<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrencyTrackingColumnsToPossTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('p_o_s_s', function (Blueprint $table) {
            $table->decimal('received_in_usd', 10, 2)->nullable()->default(0)->after('payment_type');
            $table->integer('received_in_riel')->nullable()->default(0)->after('received_in_usd');
            $table->decimal('change_in_usd', 10, 2)->nullable()->default(0)->after('received_in_riel');
            $table->integer('change_in_riel')->nullable()->default(0)->after('change_in_usd');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('p_o_s_s', function (Blueprint $table) {
            $table->dropColumn('received_in_usd');
            $table->dropColumn('received_in_riel');
            $table->dropColumn('change_in_usd');
            $table->dropColumn('change_in_riel');
        });
    }
}
