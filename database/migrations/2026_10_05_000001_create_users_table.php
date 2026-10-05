<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->string('username', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('password')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('linkedin')->nullable();
            $table->boolean('sosmed_public')->nullable()->default(true);
            $table->integer('points')->nullable()->default(0);
            $table->string('clock_mode', 20)->nullable()->default('clock');
            $table->integer('countdown_seconds')->nullable()->default(0);
            $table->integer('pomodoro_work')->nullable()->default(25);
            $table->integer('pomodoro_break')->nullable()->default(5);
            $table->string('reset_token')->nullable();
            $table->dateTime('reset_expire')->nullable();
            $table->string('role', 20)->nullable()->default('user');
            $table->integer('role_id')->nullable()->default(2);
            $table->primary('id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
