<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->integer('user_id')->unique();
            $table->string('phone_number', 32)->nullable()->unique();
            $table->char('pairing_code_hash', 64)->nullable()->unique();
            $table->dateTime('pairing_expires_at')->nullable();
            $table->dateTime('linked_at')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('whatsapp_inbound_messages', function (Blueprint $table): void {
            $table->string('message_id', 255)->primary();
            $table->string('sender', 32);
            $table->dateTime('received_at')->useCurrent();
            $table->dateTime('processed_at')->nullable();
            $table->index(['sender', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_inbound_messages');
        Schema::dropIfExists('whatsapp_accounts');
    }
};
