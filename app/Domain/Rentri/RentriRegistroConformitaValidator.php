<?php

namespace App\Domain\Rentri;

use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use App\Services\Rentri\Dto\RentriRegistroTrasmissioneRequest;
use App\Services\Rentri\Dto\TransmissionPayload;
use App\Services\Rentri\Exceptions\RentriRegistroConformitaException;

class RentriRegistroConformitaValidator
{
    public function __construct(
        private RentriCertificateServiceInterface $certificates,
    ) {}

    /**
     * @return list<array{codice: string, label: string, ok: bool, message: string|null}>
     */
    public function checklist(TransmissionPayload $payload, RentriSetting $settings): array
    {
        $items = [];

        $identificativo = trim((string) ($settings->cf_operatore ?: $settings->cf ?: ''));
        $items[] = $this->item(
            'identificativo',
            'Identificativo operatore (CF)',
            $identificativo !== '',
            $identificativo !== '' ? null : 'Configura CF operatore o CF impresa in impostazioni RENTRI.',
        );

        $numIscr = trim((string) ($settings->num_iscr_sito ?? ''));
        $items[] = $this->item(
            'num_iscr_sito',
            'Numero iscrizione sito RENTRI',
            $numIscr !== '',
            $numIscr !== '' ? null : 'Configura num_iscr_sito in onboarding RENTRI.',
        );

        $certOk = filled($settings->cert_path_encrypted) && ! $this->certificates->isExpired($settings);
        $items[] = $this->item(
            'certificato_mtls',
            'Certificato mTLS interoperabilità valido',
            $certOk,
            $certOk ? null : 'Carica un certificato mTLS valido e non scaduto.',
        );

        $periodoOk = ! $payload->periodoA->lt($payload->periodoDa);
        $items[] = $this->item(
            'periodo',
            'Periodo di trasmissione (dal / al)',
            $periodoOk,
            $periodoOk ? null : 'La data fine periodo deve essere uguale o successiva alla data inizio.',
        );

        $count = (int) ($payload->metadata['count'] ?? count($payload->movimenti));
        $items[] = $this->item(
            'movimenti',
            'Almeno un movimento nel periodo',
            $count > 0,
            $count > 0 ? null : 'Nessun movimento non trasmesso nel periodo selezionato.',
        );

        foreach ($payload->movimenti as $index => $movimento) {
            $label = 'Movimento #'.((int) ($movimento['id'] ?? ($index + 1)));
            $items = array_merge($items, $this->movimentoItems($movimento, $label));
        }

        $request = RentriRegistroTrasmissioneRequest::fromPayload($payload, $settings);
        $body = $request->body();
        $items[] = $this->item(
            'payload_ministeriale',
            'Payload ministeriale periodo_dal / periodo_al',
            filled($body['periodo_dal'] ?? null) && filled($body['periodo_al'] ?? null),
            'Verifica le date del periodo nel payload RENTRI.',
        );

        return $items;
    }

    public function assertConforme(TransmissionPayload $payload, RentriSetting $settings): void
    {
        $errors = [];
        foreach ($this->checklist($payload, $settings) as $item) {
            if (! $item['ok']) {
                $errors[] = $item['message'] ?? $item['label'];
            }
        }

        if ($errors !== []) {
            throw new RentriRegistroConformitaException($errors);
        }
    }

    public function isConforme(TransmissionPayload $payload, RentriSetting $settings): bool
    {
        foreach ($this->checklist($payload, $settings) as $item) {
            if (! $item['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $movimento
     * @return list<array{codice: string, label: string, ok: bool, message: string|null}>
     */
    private function movimentoItems(array $movimento, string $label): array
    {
        $codiceCer = trim((string) ($movimento['codice_cer'] ?? ''));
        $tipo = strtolower((string) ($movimento['tipo'] ?? ''));
        $peso = (float) ($movimento['peso_kg'] ?? 0);
        $data = trim((string) ($movimento['data'] ?? ''));

        return [
            $this->item(
                'mov_codice_cer',
                "{$label}: codice CER",
                $codiceCer !== '',
                'Codice CER obbligatorio per ogni movimento.',
            ),
            $this->item(
                'mov_tipo',
                "{$label}: tipo movimento",
                in_array($tipo, ['carico', 'scarico'], true),
                'Tipo movimento deve essere carico o scarico.',
            ),
            $this->item(
                'mov_quantita',
                "{$label}: quantità (kg)",
                $peso > 0,
                'La quantità in kg deve essere maggiore di zero.',
            ),
            $this->item(
                'mov_data',
                "{$label}: data movimento",
                $data !== '',
                'Data movimento obbligatoria.',
            ),
        ];
    }

    /**
     * @return array{codice: string, label: string, ok: bool, message: string|null}
     */
    private function item(string $codice, string $label, bool $ok, ?string $message = null): array
    {
        return [
            'codice'  => $codice,
            'label'   => $label,
            'ok'      => $ok,
            'message' => $ok ? null : ($message ?? $label),
        ];
    }
}
