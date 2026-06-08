<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vfu_registrations', function (Blueprint $table) {
            $table->string('nazionalita_proprietario', 80)->default('Italiana')->after('luogo_nascita');
            $table->string('provincia_nascita', 2)->nullable()->after('nazionalita_proprietario');
            $table->string('tipo_documento_identita', 30)->nullable()->after('provincia_nascita');
            $table->string('numero_documento_identita', 50)->nullable()->after('tipo_documento_identita');
            $table->text('note_carrozzeria')->nullable()->after('numero_documento_identita');
            $table->string('provenienza_veicolo', 30)->nullable()->after('note_carrozzeria');
            $table->boolean('targa_estera')->default(false)->after('provenienza_veicolo');
            $table->string('targa_estera_valore', 20)->nullable()->after('targa_estera');
        });
    }

    public function down(): void
    {
        Schema::table('vfu_registrations', function (Blueprint $table) {
            $table->dropColumn([
                'nazionalita_proprietario',
                'provincia_nascita',
                'tipo_documento_identita',
                'numero_documento_identita',
                'note_carrozzeria',
                'provenienza_veicolo',
                'targa_estera',
                'targa_estera_valore',
            ]);
        });
    }
};
