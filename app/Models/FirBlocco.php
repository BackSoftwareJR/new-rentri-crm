<?php

namespace App\Models;

use App\Models\Concerns\HasDemoScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FirBlocco extends Model
{
    use HasDemoScope;

    public const PROGRESSIVO_MAX = 9999;

    protected $table = 'fir_blocchi';

    protected $fillable = [
        'codice_blocco',
        'num_iscr_sito',
        'progressivo_ultimo',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'progressivo_ultimo' => 'integer',
            'is_demo'            => 'boolean',
        ];
    }

    public function firs(): HasMany
    {
        return $this->hasMany(Fir::class, 'codice_blocco', 'codice_blocco');
    }

    public function isEsaurito(): bool
    {
        return $this->progressivo_ultimo >= self::progressivoMax();
    }

    public function progressiviRimanenti(): int
    {
        return max(0, self::progressivoMax() - $this->progressivo_ultimo);
    }

    public static function progressivoMax(): int
    {
        return max(1, (int) config('services.rentri.fir_progressivo_max', self::PROGRESSIVO_MAX));
    }
}
