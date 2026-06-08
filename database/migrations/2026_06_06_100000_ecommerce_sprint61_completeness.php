<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecommerce_prodotti', function (Blueprint $table) {
            $table->string('immagine_path', 500)->nullable()->after('attivo');
        });

        Schema::table('ecommerce_ordini', function (Blueprint $table) {
            $table->string('checkout_token', 64)->nullable()->unique()->after('righe');
            $table->string('pagamento_metodo', 40)->nullable()->after('checkout_token');
            $table->text('note_checkout')->nullable()->after('pagamento_metodo');
            $table->timestamp('confermato_at')->nullable()->after('note_checkout');
            $table->timestamp('annullato_at')->nullable()->after('confermato_at');
        });
    }

    public function down(): void
    {
        Schema::table('ecommerce_ordini', function (Blueprint $table) {
            $table->dropColumn(['checkout_token', 'pagamento_metodo', 'note_checkout', 'confermato_at', 'annullato_at']);
        });

        Schema::table('ecommerce_prodotti', function (Blueprint $table) {
            $table->dropColumn('immagine_path');
        });
    }
};
