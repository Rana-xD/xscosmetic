<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembershipsTable extends Migration
{
    public function up()
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->increments('id');
            $table->string('phone', 50)->unique();
            $table->string('name');
            $table->string('rank', 20);
            $table->unsignedTinyInteger('discount_percent');
            $table->unsignedInteger('membership_years')->default(1);
            $table->date('expired_at');
            $table->timestamps();

            $table->index('name');
            $table->index('rank');
            $table->index('expired_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('memberships');
    }
}
