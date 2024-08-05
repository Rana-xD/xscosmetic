<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableTpos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('t_p_o_s', function (Blueprint $table) {
            $table->id();
            $table->string("order_no");
            $table->text("items");
            $table->string("cashier");
            $table->string("time");
            $table->string("payment_type");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('table_tpos');
    }
}
