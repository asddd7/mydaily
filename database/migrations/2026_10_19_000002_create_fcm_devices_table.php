<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fcm_devices', function (Blueprint $table): void {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->bigIncrements('id');
            $table->string('user_id', 191);
            $table->string('fid', 128)->unique('fcm_devices_fid_unique');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index('user_id', 'fcm_devices_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_devices');
    }
};
