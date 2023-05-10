<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('creator')->references('id')->on('users');
            $table->unsignedInteger('for')->references('id')->on('users');
            $table->string('description')->nullable();
            $table->enum('type',['plus','minus','plus_amn','minus_amn'])->nullable();
            $table->string('attachment')->nullable();
            $table->integer('approved')->default(1);
            $table->integer('price')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial');

    }
};
