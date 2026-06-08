<?php

namespace App\Models;

use App\Domain\Azienda\AziendaSettingService;
use App\Enums\SdiStato;
use App\Traits\BelongsToSito;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Fattura extends Model
{
    use BelongsToSito, SoftDeletes;

    protected $table = 'fatture';

    protected $fillable = [
        'numero_fattura',
        'tipo',
        'anagrafica_id',
        'data_emissione',
        'data_scadenza',
        'stato',
        'imponibile',
        'iva_percentuale',
        'iva_importo',
        'totale',
        'note',
        'metodo_pagamento',
        'riferimento_vfu_id',
        'ecommerce_ordine_id',
        'pdf_path',
        'fattura_pa_xml_path',
        'sdi_stato',
        'motivo_annullamento',
        'data_pagamento',
    ];

    protected function casts(): array
    {
        return [
            'data_emissione'  => 'date',
            'data_scadenza'   => 'date',
            'data_pagamento'  => 'date',
            'imponibile'      => 'decimal:2',
            'iva_importo'     => 'decimal:2',
            'totale'          => 'decimal:2',
            'iva_percentuale' => 'integer',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function anagrafica(): BelongsTo
    {
        return $this->belongsTo(Anagrafica::class);
    }

    public function righe(): HasMany
    {
        return $this->hasMany(RigaFattura::class)->orderBy('ordine');
    }

    public function vfu(): BelongsTo
    {
        return $this->belongsTo(VfuRegistration::class, 'riferimento_vfu_id');
    }

    public function ecommerceOrdine(): BelongsTo
    {
        return $this->belongsTo(EcommerceOrdine::class, 'ecommerce_ordine_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeBozza(Builder $query): Builder
    {
        return $query->where('stato', 'bozza');
    }

    public function scopeEmessa(Builder $query): Builder
    {
        return $query->where('stato', 'emessa');
    }

    public function scopeScadute(Builder $query): Builder
    {
        return $query->where('stato', 'scaduta')
            ->orWhere(function (Builder $q) {
                $q->where('stato', 'emessa')
                    ->whereNotNull('data_scadenza')
                    ->where('data_scadenza', '<', now()->toDateString());
            });
    }

    // ─── Static helpers ───────────────────────────────────────────────────────

    public static function numerazioneProgressiva(string $tipo = 'fattura'): string
    {
        /** @var AziendaSettingService $aziendaSettings */
        $aziendaSettings = app(AziendaSettingService::class);

        return $aziendaSettings->prossimoNumero($tipo);
    }

    // ─── Business methods ─────────────────────────────────────────────────────

    public function calcolaTotali(): void
    {
        $imponibile = $this->righe->sum('totale_riga');
        $ivaImporto = round($imponibile * ($this->iva_percentuale / 100), 2);
        $totale     = $imponibile + $ivaImporto;

        $this->imponibile  = $imponibile;
        $this->iva_importo = $ivaImporto;
        $this->totale      = $totale;
        $this->save();
    }

    // ─── Presenters ───────────────────────────────────────────────────────────

    public function statoLabel(): string
    {
        return match ($this->stato) {
            'bozza'      => 'Bozza',
            'emessa'     => 'Emessa',
            'pagata'     => 'Pagata',
            'scaduta'    => 'Scaduta',
            'annullata'  => 'Annullata',
            default      => $this->stato,
        };
    }

    public function tipoLabel(): string
    {
        return match ($this->tipo) {
            'fattura'      => 'Fattura',
            'nota_credito' => 'Nota di credito',
            'preventivo'   => 'Preventivo',
            default        => $this->tipo,
        };
    }

    public function statoColor(): string
    {
        return match ($this->stato) {
            'bozza'     => '#6b7280',
            'emessa'    => '#2563eb',
            'pagata'    => '#16a34a',
            'scaduta'   => '#dc2626',
            'annullata' => '#9ca3af',
            default     => '#6b7280',
        };
    }

    public function sdiStatoLabel(): ?string
    {
        if (blank($this->sdi_stato)) {
            return null;
        }

        return SdiStato::tryFrom($this->sdi_stato)?->label();
    }

    public function sdiStatoColor(): string
    {
        if (blank($this->sdi_stato)) {
            return '#6b7280';
        }

        return SdiStato::tryFrom($this->sdi_stato)?->color() ?? '#6b7280';
    }
}
