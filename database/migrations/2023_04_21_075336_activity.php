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
        Schema::create('users_activity', function (Blueprint $table) {
            $table->increments('id');
            $table->string('by')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('content')->nullable();
            $table->integer('agent_view')->default(0);
            $table->integer('admin_view')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_activity');

    }
};
