<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'firs',
        'fir_blocchi',
        'registro_movimenti',
        'rentri_transmissioni',
        'rentri_transazioni',
        'rentri_settings',
        'trasporti',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->boolean('is_demo')->default(false)->after('id');
                $table->index('is_demo');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropIndex(['is_demo']);
                $table->dropColumn('is_demo');
            });
        }
    }
};
