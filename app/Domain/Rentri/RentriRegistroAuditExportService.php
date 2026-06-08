<?php

namespace App\Domain\Rentri;

use App\Models\RentriTransazione;
use App\Models\RentriTransmissione;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RentriRegistroAuditExportService
{
    /**
     * @return array<string, mixed>
     */
    public function buildPayload(RentriTransmissione $transmissione): array
    {
        $transmissione->loadMissing(['movimenti.codiceCer']);

        $transazioneRentri = $this->resolveTransazione($transmissione);

        return [
            'export_version' => '1.0',
            'generated_at'   => now()->toIso8601String(),
            'trasmissione'   => [
                'id'           => $transmissione->id,
                'periodo_dal'  => $transmissione->periodo_da->toDateString(),
                'periodo_al'   => $transmissione->periodo_a->toDateString(),
                'esito'        => $transmissione->esito,
                'protocollo'   => $transmissione->response_json['protocollo'] ?? null,
                'trasmesso_at' => $transmissione->trasmesso_at?->toIso8601String(),
                'payload_hash' => $transmissione->payload_hash,
                'api_mode'     => $transmissione->response_json['api_mode'] ?? null,
            ],
            'transazione_rentri' => $transazioneRentri ? [
                'id'              => $transazioneRentri->id,
                'transazione_id'  => $transazioneRentri->transazione_id,
                'tipo_api'        => $transazioneRentri->tipo_api,
                'stato'           => $transazioneRentri->stato,
                'completed_at'    => $transazioneRentri->completed_at?->toIso8601String(),
            ] : [
                'transazione_id' => $transmissione->response_json['transazione_id'] ?? null,
                'stato'          => null,
            ],
            'movimenti' => $transmissione->movimenti->map(fn ($m) => [
                'id'             => $m->id,
                'codice_cer'     => $m->codiceCer?->codice,
                'tipo'           => $m->tipo->value,
                'peso_kg'        => (float) $m->peso_kg,
                'data_movimento' => $m->data_movimento->toIso8601String(),
                'locked_at'      => $m->locked_at?->toIso8601String(),
                'rentri_trasmesso' => $m->rentri_trasmesso,
            ])->values()->all(),
        ];
    }

    public function exportJson(RentriTransmissione $transmissione): StreamedResponse
    {
        $json = json_encode(
            $this->buildPayload($transmissione),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        return response()->streamDownload(
            fn () => print($json),
            $this->filename($transmissione, 'json'),
            ['Content-Type' => 'application/json'],
        );
    }

    public function exportCsv(RentriTransmissione $transmissione): StreamedResponse
    {
        $payload = $this->buildPayload($transmissione);

        return response()->streamDownload(function () use ($payload) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, ['Sezione', 'Campo', 'Valore'], ';');

            foreach ($payload['trasmissione'] as $key => $value) {
                fputcsv($out, ['trasmissione', $key, (string) ($value ?? '')], ';');
            }

            foreach ($payload['transazione_rentri'] as $key => $value) {
                fputcsv($out, ['transazione_rentri', $key, (string) ($value ?? '')], ';');
            }

            fputcsv($out, [], ';');
            fputcsv($out, ['movimento_id', 'codice_cer', 'tipo', 'peso_kg', 'data_movimento', 'locked_at'], ';');

            foreach ($payload['movimenti'] as $mov) {
                fputcsv($out, [
                    $mov['id'],
                    $mov['codice_cer'] ?? '',
                    $mov['tipo'],
                    $mov['peso_kg'],
                    $mov['data_movimento'],
                    $mov['locked_at'] ?? '',
                ], ';');
            }

            fclose($out);
        }, $this->filename($transmissione, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function filename(RentriTransmissione $transmissione, string $extension): string
    {
        $protocollo = $transmissione->response_json['protocollo'] ?? 'tx-'.$transmissione->id;
        $safe = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $protocollo) ?: 'audit';

        return sprintf('rentri-registro-audit-%s.%s', $safe, $extension);
    }

    private function resolveTransazione(RentriTransmissione $transmissione): ?RentriTransazione
    {
        $transazioneId = (string) ($transmissione->response_json['transazione_id'] ?? '');

        if ($transazioneId === '') {
            return null;
        }

        return RentriTransazione::query()
            ->where('transazione_id', $transazioneId)
            ->where('tipo_api', 'registro')
            ->first();
    }
}
