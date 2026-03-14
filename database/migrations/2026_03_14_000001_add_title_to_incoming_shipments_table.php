<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTitleToIncomingShipmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('incoming_shipments', function (Blueprint $table) {
            $table->string('title')->nullable()->after('reference_no');
        });

        DB::table('incoming_shipments')
            ->whereNull('title')
            ->update([
                'title' => DB::raw('reference_no'),
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('incoming_shipments', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
}
