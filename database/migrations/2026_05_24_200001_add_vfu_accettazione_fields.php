<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vfu_registrations', function (Blueprint $table) {
            $table->string('tipo_veicolo', 50)->default('Autovettura')->after('id');
            $table->string('nazione', 50)->default('Italia')->after('tipo_veicolo');
            $table->string('nome', 100)->nullable()->after('modello');
            $table->string('cognome', 100)->nullable()->after('nome');
            $table->string('proprietario', 200)->nullable()->after('cognome');
            $table->string('codice_fiscale', 16)->nullable()->after('proprietario');
            $table->string('regione', 100)->nullable()->after('codice_fiscale');
            $table->string('indirizzo', 255)->nullable()->after('regione');
            $table->string('comune', 100)->nullable()->after('indirizzo');
            $table->string('provincia', 2)->nullable()->after('comune');
            $table->date('data_nascita')->nullable()->after('provincia');
            $table->string('luogo_nascita', 100)->nullable()->after('data_nascita');
            $table->boolean('certificato_provvisorio_caricato')->default(false)->after('stato');
            $table->timestamp('data_invio_agenzia')->nullable()->after('data_accettazione');
            $table->foreignId('agenzia_anagrafica_id')->nullable()->after('data_invio_agenzia')
                ->constrained('anagrafiche')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vfu_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agenzia_anagrafica_id');
            $table->dropColumn([
                'tipo_veicolo',
                'nazione',
                'nome',
                'cognome',
                'proprietario',
                'codice_fiscale',
                'regione',
                'indirizzo',
                'comune',
                'provincia',
                'data_nascita',
                'luogo_nascita',
                'certificato_provvisorio_caricato',
                'data_invio_agenzia',
            ]);
        });
    }
};
