<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->integer('user_id');
            $table->char('token_hash', 64)->unique();
            $table->text('token');
            $table->string('user_agent', 1000)->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('push_notification_deliveries', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('push_subscription_id');
            $table->integer('task_id');
            $table->date('deadline');
            $table->timestamp('sent_at');
            $table->unique(
                ['push_subscription_id', 'task_id', 'deadline'],
                'push_delivery_subscription_task_deadline_unique'
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
        Schema::dropIfExists('push_notification_deliveries');
        Schema::dropIfExists('push_subscriptions');
    }
};
