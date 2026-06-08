<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_export_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('export_id')->unique();
            $table->string('disk', 50);
            $table->string('path', 500);
            $table->string('checksum_sha256', 64);
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('status', 20)->default('completed');
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->boolean('dry_run')->default(false);
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_export_runs');
    }
};
