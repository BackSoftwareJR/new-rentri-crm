<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_ordini', function (Blueprint $table) {
            $table->string('payment_gateway', 20)->nullable()->after('checkout_token');
            $table->string('stripe_checkout_session_id', 255)->nullable()->after('payment_gateway');
            $table->text('payment_checkout_url')->nullable()->after('stripe_checkout_session_id');

            $table->index('stripe_checkout_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_ordini', function (Blueprint $table) {
            $table->dropIndex(['stripe_checkout_session_id']);
            $table->dropColumn(['payment_gateway', 'stripe_checkout_session_id', 'payment_checkout_url']);
        });
    }
};
