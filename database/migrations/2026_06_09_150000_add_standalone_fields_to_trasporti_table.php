<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trasporti', function (Blueprint $table) {
            $table->foreignId('anagrafica_trasportatore_id')->nullable()->after('anagrafica_destinatario_id')
                ->constrained('anagrafiche')->nullOnDelete();
            $table->string('targa_mezzo', 20)->nullable()->after('anagrafica_trasportatore_id');
            $table->string('conducente', 120)->nullable()->after('targa_mezzo');
            $table->date('data_trasporto')->nullable()->after('conducente');
            $table->foreignId('vfu_registration_id')->nullable()->after('data_trasporto')
                ->constrained('vfu_registrations')->nullOnDelete();
            $table->foreignId('fir_blocco_id')->nullable()->after('vfu_registration_id')
                ->constrained('fir_blocchi')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('trasporti', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anagrafica_trasportatore_id');
            $table->dropConstrainedForeignId('vfu_registration_id');
            $table->dropConstrainedForeignId('fir_blocco_id');
            $table->dropColumn(['targa_mezzo', 'conducente', 'data_trasporto']);
        });
    }
};
