<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomingShipmentItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incoming_shipment_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('incoming_shipment_id');
            $table->string('name');
            $table->string('barcode');
            $table->unsignedInteger('qty');
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('expire_date')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedBigInteger('linked_product_id')->nullable();
            $table->timestamps();

            $table->foreign('incoming_shipment_id')->references('id')->on('incoming_shipments')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('linked_product_id')->references('id')->on('products')->onDelete('set null');

            $table->index('status');
            $table->index('barcode');
            $table->index(['status', 'barcode']);
            $table->index(['incoming_shipment_id', 'status']);
            $table->index('confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incoming_shipment_items');
    }
}
