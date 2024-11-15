<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSplitPaymentColumnsToPosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('p_o_s_s', function (Blueprint $table) {
            $table->decimal('cash_percentage', 5, 2)->nullable()->after('payment_type');
            $table->decimal('aba_percentage', 5, 2)->nullable()->after('cash_percentage');
            $table->decimal('cash_amount', 10, 2)->nullable()->after('aba_percentage');
            $table->decimal('aba_amount', 10, 2)->nullable()->after('cash_amount');
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
            $table->dropColumn('cash_percentage');
            $table->dropColumn('aba_percentage');
            $table->dropColumn('cash_amount');
            $table->dropColumn('aba_amount');
        });
    }
}
