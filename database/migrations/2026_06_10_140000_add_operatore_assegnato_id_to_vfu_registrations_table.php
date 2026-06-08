<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vfu_registrations', function (Blueprint $table) {
            $table->foreignId('operatore_assegnato_id')
                ->nullable()
                ->after('sito_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vfu_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operatore_assegnato_id');
        });
    }
};
