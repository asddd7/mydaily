<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remember_tokens', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->integer('id', true);
            $table->integer('user_id');
            $table->string('token_hash')->unique();
            $table->dateTime('expires_at');
            $table->timestamp('created_at')->useCurrent();
            $table->primary('id');
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remember_tokens');
    }
};
