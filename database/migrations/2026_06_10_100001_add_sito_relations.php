<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sito_id')->nullable()->after('id')->constrained('siti')->nullOnDelete();
        });

        Schema::table('rentri_settings', function (Blueprint $table) {
            $table->foreignId('sito_id')->nullable()->after('id')->constrained('siti')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rentri_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sito_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sito_id');
        });
    }
};
