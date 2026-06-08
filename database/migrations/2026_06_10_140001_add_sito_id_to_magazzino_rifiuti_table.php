<?php

use App\Models\Sito;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('magazzino_rifiuti', function (Blueprint $table) {
            $table->dropUnique(['codice_cer_id']);
            $table->foreignId('sito_id')->nullable()->after('id')->constrained('siti')->nullOnDelete();
            $table->unique(['codice_cer_id', 'sito_id']);
        });

        $defaultSitoId = Sito::query()->where('is_default', true)->value('id')
            ?? Sito::query()->orderBy('id')->value('id');

        if ($defaultSitoId !== null) {
            DB::table('magazzino_rifiuti')
                ->whereNull('sito_id')
                ->update(['sito_id' => $defaultSitoId]);
        }
    }

    public function down(): void
    {
        Schema::table('magazzino_rifiuti', function (Blueprint $table) {
            $table->dropUnique(['codice_cer_id', 'sito_id']);
            $table->dropConstrainedForeignId('sito_id');
            $table->unique('codice_cer_id');
        });
    }
};
