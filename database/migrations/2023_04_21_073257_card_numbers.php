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
        Schema::create('card_numbers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('card_number_name')->nullable();
            $table->string('card_number')->nullable();
            $table->string('card_number_bank')->nullable();
            $table->integer('is_enabled')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('card_numbers');

    }
};
