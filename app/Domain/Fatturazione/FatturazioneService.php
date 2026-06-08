<?php

namespace App\Domain\Fatturazione;

use App\Domain\Azienda\AziendaSettingService;
use App\Domain\Audit\ActivityLogService;
use App\Models\Fattura;
use App\Models\RigaFattura;
use App\Support\Logging\StructuredLogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class FatturazioneService
{
    public function __construct(
        private readonly StructuredLogService $log,
        private readonly ActivityLogService $audit,
    ) {}

    /**
     * @param  array{
     *   tipo?: string,
     *   anagrafica_id: int,
     *   data_emissione?: string,
     *   data_scadenza?: string|null,
     *   iva_percentuale?: int,
     *   note?: string|null,
     *   metodo_pagamento?: string|null,
     *   riferimento_vfu_id?: int|null,
     *   ecommerce_ordine_id?: int|null,
     * }  $data
     */
    public function creaFattura(array $data): Fattura
    {
        $tipo = $data['tipo'] ?? 'fattura';

        $fattura = Fattura::create([
            'numero_fattura'    => Fattura::numerazioneProgressiva($tipo),
            'tipo'              => $tipo,
            'anagrafica_id'     => $data['anagrafica_id'],
            'data_emissione'    => $data['data_emissione'] ?? now()->toDateString(),
            'data_scadenza'     => $data['data_scadenza'] ?? null,
            'stato'             => 'bozza',
            'iva_percentuale'   => $data['iva_percentuale'] ?? 22,
            'note'              => $data['note'] ?? null,
            'metodo_pagamento'  => $data['metodo_pagamento'] ?? null,
            'riferimento_vfu_id' => $data['riferimento_vfu_id'] ?? null,
            'ecommerce_ordine_id' => $data['ecommerce_ordine_id'] ?? null,
            'imponibile'        => 0,
            'iva_importo'       => 0,
            'totale'            => 0,
        ]);

        $this->log->info('business', 'fattura.creata', 'Fattura creata', [
            'entity_type' => 'fattura',
            'entity_id'   => $fattura->id,
            'numero'      => $fattura->numero_fattura,
        ]);

        $this->audit->record(
            'fatturazione',
            'Fattura creata',
            $fattura,
            [
                'numero_fattura' => $fattura->numero_fattura,
                'tipo'           => $fattura->tipo,
                'stato'          => $fattura->stato,
            ],
        );

        return $fattura;
    }

    /**
     * @param  array{
     *   descrizione: string,
     *   quantita?: float,
     *   prezzo_unitario: float,
     *   iva_percentuale?: int,
     *   ordine?: int,
     * }  $data
     */
    public function aggiungiRiga(Fattura $fattura, array $data): RigaFattura
    {
        $quantita      = (float) ($data['quantita'] ?? 1);
        $prezzoUnit    = (float) $data['prezzo_unitario'];
        $ivaPerc       = (int) ($data['iva_percentuale'] ?? $fattura->iva_percentuale);
        $totaleRiga    = round($quantita * $prezzoUnit, 2);
        $ordine        = (int) ($data['ordine'] ?? ($fattura->righe()->count() + 1));

        $riga = RigaFattura::create([
            'fattura_id'      => $fattura->id,
            'descrizione'     => $data['descrizione'],
            'quantita'        => $quantita,
            'prezzo_unitario' => $prezzoUnit,
            'iva_percentuale' => $ivaPerc,
            'totale_riga'     => $totaleRiga,
            'ordine'          => $ordine,
        ]);

        $fattura->refresh();
        $fattura->load('righe');
        $fattura->calcolaTotali();

        return $riga;
    }

    public function emettiFattura(Fattura $fattura): void
    {
        if ($fattura->stato !== 'bozza') {
            throw new \LogicException("Impossibile emettere una fattura in stato: {$fattura->stato}");
        }

        $pdfPath = $this->generaPdf($fattura);

        $fattura->update([
            'stato'         => 'emessa',
            'data_emissione' => $fattura->data_emissione ?? now()->toDateString(),
            'pdf_path'      => $pdfPath,
        ]);

        $this->log->info('business', 'fattura.emessa', 'Fattura emessa', [
            'entity_type' => 'fattura',
            'entity_id'   => $fattura->id,
            'numero'      => $fattura->numero_fattura,
        ]);

        $this->audit->record(
            'fatturazione',
            'Fattura emessa',
            $fattura->fresh(),
            [
                'numero_fattura' => $fattura->numero_fattura,
                'pdf_path'       => $pdfPath,
            ],
        );
    }

    public function registraPagamento(Fattura $fattura, Carbon $dataPagamento): void
    {
        if (! in_array($fattura->stato, ['emessa', 'scaduta'], true)) {
            throw new \LogicException("Impossibile registrare pagamento per fattura in stato: {$fattura->stato}");
        }

        $fattura->update([
            'stato'          => 'pagata',
            'data_pagamento' => $dataPagamento->toDateString(),
        ]);

        $this->log->info('business', 'fattura.pagata', 'Pagamento registrato', [
            'entity_type'    => 'fattura',
            'entity_id'      => $fattura->id,
            'numero'         => $fattura->numero_fattura,
            'data_pagamento' => $dataPagamento->toDateString(),
        ]);

        $this->audit->record(
            'fatturazione',
            'Pagamento fattura registrato',
            $fattura->fresh(),
            [
                'numero_fattura' => $fattura->numero_fattura,
                'data_pagamento' => $dataPagamento->toDateString(),
            ],
        );
    }

    public function annulla(Fattura $fattura, string $motivo): void
    {
        if ($fattura->stato === 'annullata') {
            throw new \LogicException('Fattura già annullata');
        }

        $fattura->update([
            'stato'                => 'annullata',
            'motivo_annullamento'  => $motivo,
        ]);

        $this->log->warning('business', 'fattura.annullata', 'Fattura annullata', [
            'entity_type' => 'fattura',
            'entity_id'   => $fattura->id,
            'numero'      => $fattura->numero_fattura,
            'motivo'      => $motivo,
        ]);
    }

    public function generaPdf(Fattura $fattura): string
    {
        $fattura->loadMissing(['anagrafica', 'righe', 'vfu']);

        /** @var AziendaSettingService $aziendaSettings */
        $aziendaSettings = app(AziendaSettingService::class);
        $azienda         = (object) $aziendaSettings->all();

        $logoAbsolutePath = null;
        $logoPath         = $azienda->logo_path ?? null;

        if (filled($logoPath) && Storage::disk('public')->exists($logoPath)) {
            $logoAbsolutePath = Storage::disk('public')->path($logoPath);
        }

        $pdf = Pdf::loadView('pdf.fattura', [
            'fattura'          => $fattura,
            'azienda'          => $azienda,
            'logoAbsolutePath' => $logoAbsolutePath,
        ])->setPaper('a4', 'portrait');

        $dir      = 'fatture/'.now()->year;
        $filename = strtolower($fattura->numero_fattura).'.pdf';
        $path     = $dir.'/'.$filename;

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
