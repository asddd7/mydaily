<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_manager', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_0900_ai_ci';
            $table->integer('id', true);
            $table->integer('user_id');
            $table->string('file_name');
            $table->string('file_original');
            $table->bigInteger('file_size')->nullable()->default(0);
            $table->string('file_type', 100)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->primary('id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_manager');
    }
};
