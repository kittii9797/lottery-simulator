<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDrawnNumbersTable extends Migration
{
    public function up()
    {
        Schema::create('drawn_numbers', function (Blueprint $table) {
            $table->id();
            $table->json('numbers');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('drawn_numbers');
    }
}
