<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_import_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('run_id')->unique();
            $table->string('status', 20)->default('completed');
            $table->boolean('dry_run')->default(false);
            $table->json('entities');
            $table->json('diff_summary');
            $table->unsignedInteger('total_new')->default(0);
            $table->unsignedInteger('total_updated')->default(0);
            $table->unsignedInteger('total_skipped')->default(0);
            $table->unsignedInteger('total_errors')->default(0);
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['started_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_import_sync_runs');
    }
};
