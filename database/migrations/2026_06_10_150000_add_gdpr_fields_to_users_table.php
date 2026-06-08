<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deletion_requested_at')->nullable()->after('last_login_at');
            $table->text('deletion_reason')->nullable()->after('deletion_requested_at');
            $table->timestamp('deletion_scheduled_at')->nullable()->after('deletion_reason');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['deletion_requested_at', 'deletion_reason', 'deletion_scheduled_at']);
        });
    }
};
