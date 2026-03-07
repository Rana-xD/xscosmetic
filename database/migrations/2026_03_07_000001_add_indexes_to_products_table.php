<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('name', 'products_name_index');
            $table->index('updated_at', 'products_updated_at_index');
            $table->index(['product_barcode', 'name'], 'products_barcode_name_index');
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
            $table->dropIndex('products_name_index');
            $table->dropIndex('products_updated_at_index');
            $table->dropIndex('products_barcode_name_index');
        });
    }
}
