<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rentri_transazioni', function (Blueprint $table): void {
            $table->unsignedSmallInteger('retry_count')->default(0)->after('stato');
            $table->timestamp('next_retry_at')->nullable()->after('retry_count');
            $table->timestamp('dead_letter_at')->nullable()->after('next_retry_at');

            $table->index('next_retry_at');
            $table->index('dead_letter_at');
        });
    }

    public function down(): void
    {
        Schema::table('rentri_transazioni', function (Blueprint $table): void {
            $table->dropIndex(['next_retry_at']);
            $table->dropIndex(['dead_letter_at']);
            $table->dropColumn(['retry_count', 'next_retry_at', 'dead_letter_at']);
        });
    }
};
