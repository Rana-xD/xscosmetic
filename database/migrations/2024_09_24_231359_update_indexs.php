<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateIndexs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('products', function (Blueprint $table) {
            $table->index('product_barcode');
            $table->index('name');
        });
        
        Schema::table('p_o_s_s', function (Blueprint $table) {
            $table->index('payment_type');
        });

        Schema::table('t_p_o_s', function (Blueprint $table) {
            $table->index('payment_type');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->index('date');
        });

        Schema::table('change', function (Blueprint $table) {
            $table->index('date');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('product_barcode');
            $table->dropIndex('name');
        });

        Schema::table('p_o_s_s', function (Blueprint $table) {
            $table->dropIndex('payment_type');
        });

        Schema::table('t_p_o_s', function (Blueprint $table) {
            $table->dropIndex('payment_type');
        });


        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('date');
        });

        Schema::table('change', function (Blueprint $table) {
            $table->dropIndex('date');
        });
    }
}
