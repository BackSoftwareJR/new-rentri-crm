<?php

namespace App\Models;

use App\Models\Concerns\HasDemoScope;
use App\Domain\Anagrafiche\AuthorizationComplianceService;
use Database\Factories\AnagraficaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anagrafica extends Model
{
    /** @use HasFactory<AnagraficaFactory> */
    use HasDemoScope;
    use HasFactory;

    public const TIPI = [
        'trasportatore',
        'impianto',
        'privato',
        'agenzia_pratiche',
    ];

    protected $table = 'anagrafiche';

    protected $fillable = [
        'tipo',
        'ragione_sociale',
        'piva',
        'codice_fiscale',
        'codice_sdi',
        'pec',
        'indirizzo',
        'citta',
        'cap',
        'provincia',
        'telefono',
        'email',
        'gestisce_trasporti',
        'rentri_soggetto_id',
        'note',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'gestisce_trasporti' => 'boolean',
            'is_demo'            => 'boolean',
        ];
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(Authorization::class, 'anagrafica_id');
    }

    public function trasportiComeDestinatario(): HasMany
    {
        return $this->hasMany(Trasporto::class, 'anagrafica_destinatario_id');
    }

    public function hasValidAuthorization(): bool
    {
        return app(AuthorizationComplianceService::class)->hasValidAuthorization($this);
    }

    public function tipoLabel(): string
    {
        return match ($this->tipo) {
            'trasportatore' => 'Trasportatore',
            'impianto' => 'Impianto',
            'privato' => 'Privato',
            'agenzia_pratiche' => 'Agenzia Pratiche',
            default => $this->tipo,
        };
    }
}
