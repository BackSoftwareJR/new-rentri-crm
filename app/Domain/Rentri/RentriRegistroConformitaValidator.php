<?php

namespace App\Domain\Rentri;

use App\Models\CodiceCer;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use App\Services\Rentri\Dto\RentriRegistroTrasmissioneRequest;
use App\Services\Rentri\Dto\TransmissionPayload;
use App\Services\Rentri\Exceptions\RentriRegistroConformitaException;
use Illuminate\Support\Carbon;

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
     * @return list<string>
     */
    public function movimentoErrors(RegistroMovimento $movimento): array
    {
        $movimento->loadMissing(['codiceCer', 'source']);

        return $this->movimentoValidationErrors([
            'id' => $movimento->id,
            'tipo' => $movimento->tipo->value,
            'codice_cer' => $movimento->codiceCer?->codice ?? '',
            'codice_cer_id' => $movimento->codice_cer_id,
            'peso_kg' => (float) $movimento->peso_kg,
            'data' => $movimento->data_movimento?->toIso8601String() ?? '',
            'source_type' => $movimento->source_type,
            'source_id' => $movimento->source_id,
        ], 'Movimento #'.$movimento->id);
    }

    public function isMovimentoConforme(RegistroMovimento $movimento): bool
    {
        return $this->movimentoErrors($movimento) === [];
    }

    /**
     * @param  iterable<int, RegistroMovimento>  $movimenti
     * @return array<int, array{ok: bool, errors: list<string>}>
     */
    public function batchMovimentoConformita(iterable $movimenti): array
    {
        $results = [];

        foreach ($movimenti as $movimento) {
            $errors = $this->movimentoErrors($movimento);
            $results[$movimento->id] = [
                'ok' => $errors === [],
                'errors' => $errors,
            ];
        }

        return $results;
    }

    /**
     * @param  list<array<string, mixed>>  $movimenti
     * @return array{count: int, by_id: array<int, list<string>>}
     */
    public function payloadMovimentoErrorSummary(array $movimenti): array
    {
        $byId = [];
        $count = 0;

        foreach ($movimenti as $index => $movimento) {
            $label = 'Movimento #'.((int) ($movimento['id'] ?? ($index + 1)));
            $errors = $this->movimentoValidationErrors($movimento, $label);

            if ($errors !== []) {
                $count++;
                $byId[(int) ($movimento['id'] ?? ($index + 1))] = $errors;
            }
        }

        return ['count' => $count, 'by_id' => $byId];
    }

    /**
     * @param  array<string, mixed>  $movimento
     * @return list<array{codice: string, label: string, ok: bool, message: string|null}>
     */
    private function movimentoItems(array $movimento, string $label): array
    {
        $errors = $this->movimentoValidationErrors($movimento, $label);

        if ($errors === []) {
            return [
                $this->item('mov_conformita', "{$label}: conformità RENTRI", true),
            ];
        }

        return array_map(
            fn (string $message, int $index) => $this->item(
                'mov_error_'.$index,
                "{$label}: conformità RENTRI",
                false,
                $message,
            ),
            $errors,
            array_keys($errors),
        );
    }

    /**
     * @param  array<string, mixed>  $movimento
     * @return list<string>
     */
    private function movimentoValidationErrors(array $movimento, string $label): array
    {
        $errors = [];

        $codiceCer = trim((string) ($movimento['codice_cer'] ?? ''));
        if ($codiceCer === '') {
            $errors[] = "{$label}: codice CER obbligatorio.";
        }

        $tipo = strtolower((string) ($movimento['tipo'] ?? ''));
        if (! in_array($tipo, ['carico', 'scarico'], true)) {
            $errors[] = "{$label}: tipo movimento deve essere carico o scarico.";
        }

        $peso = (float) ($movimento['peso_kg'] ?? 0);
        if ($peso <= 0) {
            $errors[] = "{$label}: la quantità in kg deve essere maggiore di zero.";
        }

        $dataRaw = trim((string) ($movimento['data'] ?? ''));
        if ($dataRaw === '') {
            $errors[] = "{$label}: data movimento obbligatoria.";
        } elseif (Carbon::parse($dataRaw)->isAfter(now())) {
            $errors[] = "{$label}: la data movimento non può essere futura.";
        }

        $cerAttivo = $this->isCerAttivo($movimento);
        if (! $cerAttivo) {
            $errors[] = "{$label}: il codice CER non è attivo nel catalogo locale.";
        }

        if ($tipo === 'scarico' && ($movimento['source_type'] ?? null) !== MagazzinoCaricoManuale::class) {
            if (! $this->hasLinkedFir($movimento)) {
                $errors[] = "{$label}: lo scarico richiede un FIR collegato al trasporto.";
            }
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $movimento
     */
    private function isCerAttivo(array $movimento): bool
    {
        if (isset($movimento['codice_cer_id'])) {
            $cer = CodiceCer::query()->find((int) $movimento['codice_cer_id']);

            return $cer !== null && $cer->attivo;
        }

        $codice = trim((string) ($movimento['codice_cer'] ?? ''));
        if ($codice === '') {
            return false;
        }

        return CodiceCer::query()
            ->where('codice', $codice)
            ->where('attivo', true)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $movimento
     */
    private function hasLinkedFir(array $movimento): bool
    {
        if (($movimento['source_type'] ?? null) !== Trasporto::class) {
            return false;
        }

        $trasportoId = (int) ($movimento['source_id'] ?? 0);
        if ($trasportoId <= 0) {
            return false;
        }

        $trasporto = Trasporto::query()
            ->with(['fir', 'firCollegato'])
            ->find($trasportoId);

        if ($trasporto === null) {
            return false;
        }

        return ($trasporto->firCollegato ?? $trasporto->fir) !== null;
    }

    /**
     * @return array{codice: string, label: string, ok: bool, message: string|null}
     */
    private function item(string $codice, string $label, bool $ok, ?string $message = null): array
    {
        return [
            'codice' => $codice,
            'label' => $label,
            'ok' => $ok,
            'message' => $ok ? null : ($message ?? $label),
        ];
    }
}
