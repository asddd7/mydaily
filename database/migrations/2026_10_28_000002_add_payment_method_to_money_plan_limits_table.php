<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('money_plan_limits', function (Blueprint $table): void {
            $table->enum('payment_method', ['cash', 'online'])
                ->default('cash')
                ->after('period_month');

            $table->dropUnique('money_plan_limits_user_period_unique');

            $table->unique(
                ['username', 'period_year', 'period_month', 'payment_method'],
                'money_plan_limits_user_period_method_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('money_plan_limits', function (Blueprint $table): void {
            $table->dropUnique('money_plan_limits_user_period_method_unique');

            $table->unique(
                ['username', 'period_year', 'period_month'],
                'money_plan_limits_user_period_unique'
            );

            $table->dropColumn('payment_method');
        });
    }
};
