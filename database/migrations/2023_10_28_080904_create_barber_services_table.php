<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('barber_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barber_id')->nullable();
            $table->foreign('barber_id')->references('id')->on('barbers')->onDelete('cascade');
            $table->string('title');
            $table->longtext('description')->nullable();
            $table->double('price');
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1);
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
        Schema::dropIfExists('barber_services');
    }
};
