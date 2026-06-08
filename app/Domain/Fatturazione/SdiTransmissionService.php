<?php

namespace App\Domain\Fatturazione;

use App\Domain\Audit\ActivityLogService;
use App\Enums\SdiStato;
use App\Models\Fattura;
use App\Support\Logging\StructuredLogService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;

class SdiTransmissionService
{
    public function __construct(
        private readonly SdiRuntimeModeService $runtime,
        private readonly FatturaPaXmlGeneratorService $xmlGenerator,
        private readonly StructuredLogService $log,
        private readonly ActivityLogService $audit,
    ) {}

    /**
     * @return array{protocollo: string, stub: bool, sdi_stato: string}
     */
    public function transmit(Fattura $fattura): array
    {
        if (! in_array($fattura->stato, ['emessa', 'pagata', 'scaduta'], true)) {
            throw new LogicException('Trasmissione SDI consentita solo per fatture emesse.');
        }

        if ($fattura->sdi_stato === SdiStato::Inviata->value) {
            throw new LogicException('Fattura già trasmessa a SDI.');
        }

        $fattura->loadMissing(['anagrafica', 'righe']);

        if (! $fattura->fattura_pa_xml_path) {
            $this->xmlGenerator->generate($fattura);
            $fattura->refresh();
        }

        $xml = Storage::disk('local')->get($fattura->fattura_pa_xml_path);

        if (blank($xml)) {
            throw new SdiTransmissionException('XML FatturaPA non trovato o vuoto.');
        }

        $result = $this->runtime->isStub()
            ? $this->transmitStub($fattura, $xml)
            : $this->transmitLive($fattura, $xml);

        $fattura->update(['sdi_stato' => $result['sdi_stato']]);

        $this->log->info('business', 'fattura.sdi_inviata', 'Fattura trasmessa a SDI', [
            'entity_type' => 'fattura',
            'entity_id'   => $fattura->id,
            'numero'      => $fattura->numero_fattura,
            'protocollo'  => $result['protocollo'],
            'stub'        => $result['stub'],
        ]);

        $this->audit->record(
            'fatturazione',
            'Fattura trasmessa a SDI',
            $fattura->fresh(),
            [
                'numero_fattura' => $fattura->numero_fattura,
                'protocollo'     => $result['protocollo'],
                'stub'           => $result['stub'],
            ],
        );

        return $result;
    }

    /**
     * @return array{protocollo: string, stub: true, sdi_stato: string}
     */
    private function transmitStub(Fattura $fattura, string $xml): array
    {
        $protocollo = sprintf(
            'SDI-STUB-%s-%s',
            strtoupper(Str::slug($fattura->numero_fattura, '')),
            strtoupper(Str::random(8)),
        );

        return [
            'protocollo' => $protocollo,
            'stub'       => true,
            'sdi_stato'  => SdiStato::Inviata->value,
        ];
    }

    /**
     * @return array{protocollo: string, stub: false, sdi_stato: string}
     */
    private function transmitLive(Fattura $fattura, string $xml): array
    {
        $endpoint = (string) config('services.sdi.submit_url', '');

        if ($endpoint === '') {
            throw new SdiTransmissionException(
                'SDI live: endpoint non configurato (SDI_SUBMIT_URL).',
            );
        }

        $timeout = (int) config('services.sdi.timeout', 30);

        $response = Http::timeout($timeout)
            ->withHeaders([
                'Content-Type' => 'application/xml',
                'Accept'       => 'application/json',
            ])
            ->withBody($xml, 'application/xml')
            ->post($endpoint);

        if (! $response->successful()) {
            throw new SdiTransmissionException(
                'SDI live: risposta HTTP '.$response->status().'.',
            );
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];
        $protocollo = (string) ($body['protocollo'] ?? $body['id'] ?? '');

        if ($protocollo === '') {
            throw new SdiTransmissionException('SDI live: protocollo mancante nella risposta.');
        }

        return [
            'protocollo' => $protocollo,
            'stub'       => false,
            'sdi_stato'  => SdiStato::Inviata->value,
        ];
    }
}
