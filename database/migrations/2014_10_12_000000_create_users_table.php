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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('group_id')->references('id')->on('groups');
            $table->unsignedInteger('creator')->references('id')->on('users');
            $table->boolean('is_enabled')->default(true);
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username', 20)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('expire_date')->nullable();
            $table->enum('expire_type',['no_expire','minutes','month','hours','days','year'])->default('no_expire');
            $table->enum('role',['admin','user','agent'])->default('user');
            $table->string('expire_value')->nullable();
            $table->integer('is_online')->default(0);
            $table->integer('expire_set')->default(0);
            $table->integer('multi_login')->default(200);
            $table->string('password');
            $table->string('default_password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
