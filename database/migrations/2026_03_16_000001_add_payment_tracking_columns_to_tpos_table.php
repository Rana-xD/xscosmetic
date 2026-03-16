<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentTrackingColumnsToTposTable extends Migration
{
    public function up()
    {
        Schema::table('t_p_o_s', function (Blueprint $table) {
            $table->decimal('received_in_usd', 10, 2)->nullable()->default(0)->after('payment_type');
            $table->integer('received_in_riel')->nullable()->default(0)->after('received_in_usd');
            $table->decimal('change_in_usd', 10, 2)->nullable()->default(0)->after('received_in_riel');
            $table->integer('change_in_riel')->nullable()->default(0)->after('change_in_usd');
            $table->decimal('cash_percentage', 5, 2)->nullable()->after('change_in_riel');
            $table->decimal('aba_percentage', 5, 2)->nullable()->after('cash_percentage');
            $table->decimal('cash_amount', 10, 2)->nullable()->after('aba_percentage');
            $table->decimal('aba_amount', 10, 2)->nullable()->after('cash_amount');
        });
    }

    public function down()
    {
        Schema::table('t_p_o_s', function (Blueprint $table) {
            $table->dropColumn('received_in_usd');
            $table->dropColumn('received_in_riel');
            $table->dropColumn('change_in_usd');
            $table->dropColumn('change_in_riel');
            $table->dropColumn('cash_percentage');
            $table->dropColumn('aba_percentage');
            $table->dropColumn('cash_amount');
            $table->dropColumn('aba_amount');
        });
    }
}
