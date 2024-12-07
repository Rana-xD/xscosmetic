<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalInfoToPossTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('p_o_s_s', function (Blueprint $table) {
            $table->json('additional_info')->nullable()->after('items');
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
            $table->dropColumn('additional_info');
        });
    }
}
