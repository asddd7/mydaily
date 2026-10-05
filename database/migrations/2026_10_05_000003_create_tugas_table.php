<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->integer('user_id');
            $table->integer('parent_id')->nullable();
            $table->string('nama_tugas', 100);
            $table->date('deadline');
            $table->boolean('selesai')->default(false);
            $table->integer('urutan')->nullable()->default(0);
            $table->dateTime('selesai_at')->nullable();
            $table->enum('recurring_type', ['none', 'daily', 'weekly', 'monthly', 'yearly'])
                ->nullable()
                ->default('none');
            $table->boolean('recurring_generated')->nullable()->default(false);
            $table->string('kategori', 100)->nullable()->default('daily');
            $table->primary('id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};
