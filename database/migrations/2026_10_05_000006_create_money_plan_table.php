<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('money_plan', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
            $table->integer('id', true);
            $table->string('username', 100)->nullable();
            $table->enum('type', ['income', 'expense'])->nullable();
            $table->string('category', 100)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->date('tanggal')->nullable();
            $table->enum('payment_method', ['cash', 'online'])->nullable()->default('cash');
            $table->primary('id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('money_plan');
    }
};
