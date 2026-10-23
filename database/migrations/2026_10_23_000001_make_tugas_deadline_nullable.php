<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tugas', function (Blueprint $table) {
            $table->date('deadline')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Isi tanggal untuk data NULL sebelum mengembalikan NOT NULL.
        DB::table('tugas')
            ->whereNull('deadline')
            ->update(['deadline' => now()->toDateString()]);

        Schema::table('tugas', function (Blueprint $table) {
            $table->date('deadline')->nullable(false)->change();
        });
    }
};