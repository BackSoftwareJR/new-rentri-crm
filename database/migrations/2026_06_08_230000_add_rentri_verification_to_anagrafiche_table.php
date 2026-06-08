<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anagrafiche', function (Blueprint $table) {
            if (! Schema::hasColumn('anagrafiche', 'rentri_verificato_at')) {
                $table->timestamp('rentri_verificato_at')->nullable()->after('rentri_soggetto_id');
            }

            if (! Schema::hasColumn('anagrafiche', 'rentri_iscrizione_numero')) {
                $table->string('rentri_iscrizione_numero', 100)->nullable()->after('rentri_verificato_at');
            }

            if (! Schema::hasColumn('anagrafiche', 'rentri_verificato_esito')) {
                $table->string('rentri_verificato_esito', 20)->nullable()->after('rentri_iscrizione_numero')
                    ->comment('iscritto | non_trovato');
            }
        });
    }

    public function down(): void
    {
        Schema::table('anagrafiche', function (Blueprint $table) {
            $table->dropColumn([
                'rentri_verificato_at',
                'rentri_iscrizione_numero',
                'rentri_verificato_esito',
            ]);
        });
    }
};
