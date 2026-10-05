<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notes', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->integer('user_id');
            $table->string('title');
            $table->longText('content');
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent();
            $table->string('image')->nullable();
            $table->primary('id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
