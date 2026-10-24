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
    Schema::create('money_plan_receipts', function (Blueprint $table) {
        $table->id();

        // money_plan.id pada database kamu bertipe INT biasa,
        // jadi gunakan integer(), bukan foreignId().
        $table->integer('money_plan_id');

        $table->string('file_path', 500);
        $table->string('original_name', 255);
        $table->string('mime_type', 100);
        $table->unsignedBigInteger('file_size')->default(0);

        $table->timestamps();

        $table->foreign('money_plan_id')
            ->references('id')
            ->on('money_plan')
            ->cascadeOnDelete();
    });
}

public function down(): void
{
    Schema::dropIfExists('money_plan_receipts');
}
};
