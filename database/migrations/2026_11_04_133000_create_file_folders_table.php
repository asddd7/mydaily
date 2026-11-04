<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('file_folders')) {
            Schema::create('file_folders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('name', 100);
                $table->timestamps();

                $table->index(['user_id', 'name']);
            });
        }

        if (Schema::hasTable('file_manager')
            && ! Schema::hasColumn('file_manager', 'folder_id')) {
            Schema::table('file_manager', function (Blueprint $table) {
                $table->foreignId('folder_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('file_folders')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('file_manager')
            && Schema::hasColumn('file_manager', 'folder_id')) {
            Schema::table('file_manager', function (Blueprint $table) {
                $table->dropConstrainedForeignId('folder_id');
            });
        }

        Schema::dropIfExists('file_folders');
    }
};