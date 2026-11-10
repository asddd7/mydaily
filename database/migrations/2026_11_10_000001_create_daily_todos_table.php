<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_todos', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->integer('user_id');
            $table->date('todo_date');
            $table->string('title', 255);
            $table->boolean('is_completed')->default(false);
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent();
            $table->primary('id');
            $table->index(['user_id', 'todo_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_todos');
    }
};
