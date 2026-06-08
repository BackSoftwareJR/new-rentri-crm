<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_disputes', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_dispute_id')->unique()->comment('Stripe dispute ID (dp_...)');
            $table->foreignId('ordine_id')->nullable()->constrained('ecommerce_ordini')->nullOnDelete();
            $table->unsignedInteger('amount')->comment('Amount in smallest currency unit (cents)');
            $table->string('currency', 3)->default('eur');
            $table->string('reason')->nullable()->comment('Stripe dispute reason code');
            $table->string('status')->index()->comment('Stripe dispute status');
            $table->timestamp('evidence_due_by')->nullable()->comment('Deadline for submitting evidence');
            $table->json('metadata')->nullable()->comment('Full Stripe dispute object snapshot');
            $table->timestamps();

            $table->index(['status', 'evidence_due_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_disputes');
    }
};
