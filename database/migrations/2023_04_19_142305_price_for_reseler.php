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
        Schema::create('price_for_reseler', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('group_id')->references('id')->on('groups');
            $table->unsignedInteger('reseler_id')->references('id')->on('users');
            $table->string('price')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_for_reseler');

    }
};
