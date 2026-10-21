<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas', function (Blueprint $table): void {
            $table->dateTime('reminder_at')->nullable()->after('deadline');
            $table->dateTime('reminder_sent_at')->nullable()->after('reminder_at');
        });

        Schema::create('task_reminder_deliveries', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('push_subscription_id');
            $table->integer('task_id');
            $table->dateTime('reminder_at');
            $table->timestamp('sent_at');
            $table->unique(
                ['push_subscription_id', 'task_id', 'reminder_at'],
                'task_reminder_delivery_unique'
            );
            $table->foreign('push_subscription_id')
                ->references('id')
                ->on('push_subscriptions')
                ->cascadeOnDelete();
            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reminder_deliveries');

        Schema::table('tugas', function (Blueprint $table): void {
            $table->dropColumn(['reminder_at', 'reminder_sent_at']);
        });
    }
};
