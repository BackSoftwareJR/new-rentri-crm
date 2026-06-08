<?php

namespace App\Domain\Mud;

use App\Domain\Audit\ActivityLogService;
use App\Domain\Notifications\NotificationService;
use App\Enums\MudStato;
use App\Enums\NotificationEvent;
use App\Mail\MudInvioTelematicoMail;
use App\Models\MudDichiarazione;
use Illuminate\Validation\ValidationException;

class MudInvioTelematicoService
{
    public function __construct(
        private MudXmlValidationService $xmlValidation,
        private MudService $mud,
        private MudTelematicoTransmissionService $transmission,
        private MudTelematicoRuntimeModeService $runtime,
    ) {}

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string}>
     */
    public function preInvioChecklist(MudDichiarazione $dichiarazione): array
    {
        $validation = $this->xmlValidation->validate($dichiarazione, $this->mud);

        $items = [
            [
                'key'   => 'stato_completata',
                'label' => 'Dichiarazione completata',
                'ok'    => $dichiarazione->stato === MudStato::Completata,
                'hint'  => 'Completare la bozza prima dell\'invio telematico.',
            ],
            [
                'key'   => 'export_payload',
                'label' => 'Export payload presente',
                'ok'    => ! empty($dichiarazione->export_payload),
                'hint'  => null,
            ],
            [
                'key'   => 'righe_cer',
                'label' => 'Righe CER aggregate',
                'ok'    => count($dichiarazione->righe ?? []) > 0,
                'hint'  => 'Nessun movimento registro nell\'anno di riferimento.',
            ],
            [
                'key'   => 'xml_valido',
                'label' => 'XML MUD valido (schema '.MudXmlValidationService::SCHEMA_VERSION.')',
                'ok'    => $validation['valid'],
                'hint'  => $validation['errors'][0] ?? null,
            ],
        ];

        if (! $this->runtime->isStub()) {
            $endpoints = app(MudTelematicoEndpoints::class);
            $baseUrl = $endpoints->baseUrl();

            $items[] = [
                'key'   => 'endpoint_configurato',
                'label' => 'Gateway MUD configurato ('.$endpoints->environmentLabel().')',
                'ok'    => $baseUrl !== '',
                'hint'  => 'Impostare MUD_TELEMATICO_ENV e RENTRI_BASE_URL_* oppure MUD_TELEMATICO_BASE_URL.',
            ];
        }

        return $items;
    }

    public function canInviare(MudDichiarazione $dichiarazione): bool
    {
        return collect($this->preInvioChecklist($dichiarazione))->every(fn (array $item) => $item['ok']);
    }

    public function invia(MudDichiarazione $dichiarazione, int $userId): MudDichiarazione
    {
        if ($dichiarazione->stato !== MudStato::Completata) {
            throw new \InvalidArgumentException('Solo le dichiarazioni completate possono essere inviate.');
        }

        if (! $this->canInviare($dichiarazione)) {
            throw ValidationException::withMessages([
                'invio' => 'Checklist pre-invio non superata. Verificare XML e dati MUD.',
            ]);
        }

        $validation = $this->xmlValidation->validate($dichiarazione, $this->mud);
        $xml = (string) ($validation['xml'] ?? '');

        try {
            $risposta = $this->transmission->submitAndWait($dichiarazione, $xml);
        } catch (MudTelematicoTransmissionException $e) {
            throw ValidationException::withMessages([
                'invio' => 'Invio telematico MUD fallito: '.$e->getMessage(),
            ]);
        }

        $protocollo = (string) ($risposta['protocollo'] ?? '');

        if (! $this->runtime->isStub()) {
            $context = $this->transmission->buildSubmitContext($dichiarazione, $xml);
            if ($context['crm_audit'] !== []) {
                $risposta['crm_audit'] = $context['crm_audit'];
            }
        }

        $dichiarazione->update([
            'stato'            => MudStato::Inviata,
            'inviata_at'       => now(),
            'invio_protocollo' => $protocollo,
            'invio_risposta'   => $risposta,
        ]);

        $fresh = $dichiarazione->fresh();

        $description = $this->runtime->isStub()
            ? 'Invio telematico MUD stub completato'
            : 'Invio telematico MUD live completato';

        app(ActivityLogService::class)->record(
            'mud',
            $description,
            $fresh,
            [
                'anno_riferimento' => $fresh->anno_riferimento,
                'protocollo'       => $protocollo,
                'esito'            => $risposta['esito'] ?? null,
                'canale'           => $risposta['canale'] ?? null,
                'modo'             => $this->runtime->modeLabel(),
            ],
            $userId,
        );

        app(NotificationService::class)->dispatch(
            NotificationEvent::MudInvioTelematico,
            new MudInvioTelematicoMail($fresh, $protocollo),
            context: [
                'anno_riferimento' => $fresh->anno_riferimento,
                'protocollo'       => $protocollo,
            ],
        );

        return $fresh;
    }

    /**
     * @deprecated Preferire {@see invia()}; mantenuto per compatibilità test Sprint 65.
     */
    public function inviaStub(MudDichiarazione $dichiarazione, int $userId): MudDichiarazione
    {
        return $this->invia($dichiarazione, $userId);
    }
}
