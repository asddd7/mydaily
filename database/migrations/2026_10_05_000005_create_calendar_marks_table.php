<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_marks', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->integer('user_id');
            $table->date('tanggal');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->boolean('selesai')->nullable()->default(false);
            $table->string('category', 50)->nullable()->default('general');
            $table->string('color', 20)->nullable()->default('#3b82f6');
            $table->primary('id');
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_marks');
    }
};
