<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('money_plan_limits', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id');
            $table->string('username', 100);
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(
                ['username', 'period_year', 'period_month'],
                'money_plan_limits_user_period_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('money_plan_limits');
    }
};
