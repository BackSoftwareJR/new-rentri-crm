<?php

namespace App\Domain\Vfu;

use App\Domain\Audit\ActivityLogService;
use App\Domain\Magazzino\MagazzinoService;
use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\VfuStato;
use App\Models\CodiceCer;
use App\Models\RegistroMovimento;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Services\Push\WebPushService;
use App\Support\Logging\StructuredLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VfuAccettazioneService
{
    public const CER_VFU_ACCETTAZIONE = '16.01.04*';

    public function __construct(
        private readonly VfuDocumentService $documentService,
        private readonly MagazzinoService $magazzino,
        private readonly StructuredLogService $logger,
        private readonly NotificationService $notifications,
        private readonly VfuNotificationService $vfuNotifications,
        private readonly ActivityLogService $audit,
        private readonly WebPushService $webPush,
    ) {}

    public function query(array $filters = []): Builder
    {
        $query = VfuRegistration::query()
            ->forActiveSito()
            ->withCount([
                'documents as has_cert_definitivo' => fn ($q) => $q->where(
                    'tipo',
                    \App\Enums\VfuTipoDocumento::CertificatoRottamazioneDefinitivo
                ),
            ])
            ->orderByDesc('created_at');

        if (! empty($filters['stato'])) {
            $stato = VfuStato::tryFrom($filters['stato']);
            if ($stato) {
                $query->where('stato', $stato);
            }
        }

        if (! empty($filters['search'])) {
            $term = '%'.trim($filters['search']).'%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('targa', 'like', $term)
                    ->orWhere('telaio', 'like', $term)
                    ->orWhere('codice_motore', 'like', $term)
                    ->orWhere('marca', 'like', $term)
                    ->orWhere('modello', 'like', $term)
                    ->orWhere('proprietario', 'like', $term);
            });
        }

        return $query;
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    public function find(int $id): VfuRegistration
    {
        return VfuRegistration::with(['documents', 'agenzia'])->findOrFail($id);
    }

    /**
     * @return array{bozza: int, in_accettazione: int, accettato: int, attesa_bonifica: int, totale: int}
     */
    public function kpi(): array
    {
        $counts = VfuRegistration::query()
            ->forActiveSito()
            ->selectRaw('stato, COUNT(*) as total')
            ->groupBy('stato')
            ->pluck('total', 'stato');

        return [
            'bozza' => (int) ($counts[VfuStato::Bozza->value] ?? 0),
            'in_accettazione' => (int) ($counts[VfuStato::InAccettazione->value] ?? 0),
            'accettato' => (int) ($counts[VfuStato::Accettato->value] ?? 0)
                + (int) ($counts[VfuStato::AttesaBonifica->value] ?? 0),
            'in_bonifica' => (int) ($counts[VfuStato::InBonifica->value] ?? 0),
            'totale' => (int) VfuRegistration::query()->forActiveSito()->count(),
        ];
    }

    public function saveDraft(?VfuRegistration $existing, array $data): VfuRegistration
    {
        $payload = $this->normalizePayload($data);
        $payload['stato'] = $existing?->stato === VfuStato::Accettato
            ? VfuStato::Accettato
            : ($existing?->stato ?? VfuStato::InAccettazione);

        if ($existing) {
            $existing->update($payload);
            $registration = $existing->fresh('documents');
            $this->auditVfu('VFU aggiornato', $registration);

            return $registration;
        }

        $byTelaio = ! empty($payload['telaio'])
            ? VfuRegistration::where('telaio', $payload['telaio'])->first()
            : null;
        $byTarga = ! empty($payload['targa'])
            ? VfuRegistration::where('targa', $payload['targa'])->first()
            : null;

        if ($byTelaio) {
            $byTelaio->update($payload);
            $registration = $byTelaio->fresh('documents');
            $this->auditVfu('VFU aggiornato', $registration);

            return $registration;
        }

        if ($byTarga) {
            $byTarga->update($payload);
            $registration = $byTarga->fresh('documents');
            $this->auditVfu('VFU aggiornato', $registration);

            return $registration;
        }

        $payload['stato'] = VfuStato::Bozza;

        $registration = VfuRegistration::create($payload);
        $this->auditVfu('VFU creato', $registration);

        return $registration;
    }

    public function completeAccettazione(VfuRegistration $registration): VfuRegistration
    {
        if (! $this->documentService->hasRequiredDocuments($registration)) {
            throw ValidationException::withMessages([
                'documents' => 'Caricare almeno documento identità e carta di circolazione.',
            ]);
        }

        if ((float) $registration->peso_kg <= 0) {
            throw ValidationException::withMessages([
                'peso_kg' => 'Indicare il peso del veicolo per registrare il carico in magazzino.',
            ]);
        }

        return DB::transaction(function () use ($registration) {
            $registration->forceFill([
                'stato'             => VfuStato::Accettato,
                'data_accettazione' => now()->toDateString(),
            ])->save();

            $this->registraCaricoVfu($registration->fresh());

            $fresh = $registration->fresh(['documents', 'registroMovimenti']);
            $this->auditVfu('VFU accettazione completata', $fresh, [
                'stato' => VfuStato::Accettato->value,
            ]);

            try {
                $this->webPush->sendToRoles(
                    'operatore',
                    'Nuovo VFU: '.$fresh->targa,
                    'Pronto per bonifica',
                    route('operatore.bonifica.wizard', $fresh),
                );
            } catch (\Throwable) {
                // Push failures must never break core workflow.
            }

            return $fresh;
        });
    }

    public function registraCaricoVfu(VfuRegistration $registration): ?RegistroMovimento
    {
        if ((float) $registration->peso_kg <= 0) {
            return null;
        }

        $already = RegistroMovimento::query()
            ->where('source_type', RegistroMovimento::SOURCE_VFU_REGISTRATION)
            ->where('source_id', $registration->id)
            ->where('tipo', RegistroMovimentoTipo::Carico)
            ->exists();

        if ($already) {
            return null;
        }

        $cer = CodiceCer::where('codice', self::CER_VFU_ACCETTAZIONE)->first();
        if (! $cer) {
            throw new \RuntimeException('Codice CER '.self::CER_VFU_ACCETTAZIONE.' non configurato.');
        }

        $movimento = RegistroMovimento::create([
            'tipo' => RegistroMovimentoTipo::Carico,
            'codice_cer_id' => $cer->id,
            'peso_kg' => $registration->peso_kg,
            'data_movimento' => now(),
            'source_type' => RegistroMovimento::SOURCE_VFU_REGISTRATION,
            'source_id' => $registration->id,
            'note' => "Accettazione VFU — Targa: {$registration->targa}",
        ]);

        $this->magazzino->addPeso($cer->id, (float) $registration->peso_kg);

        return $movimento;
    }

    public function inviaAgenzia(VfuRegistration $registration, int $anagraficaId): VfuRegistration
    {
        $registration->forceFill([
            'stato'                 => VfuStato::InviatoAgenzia,
            'agenzia_anagrafica_id' => $anagraficaId,
            'data_invio_agenzia'    => now(),
        ])->save();

        $fresh = $registration->fresh(['agenzia', 'documents']);
        $this->auditVfu('VFU inviato ad agenzia', $fresh, [
            'agenzia_anagrafica_id' => $anagraficaId,
        ]);

        return $fresh;
    }

    public function rottama(VfuRegistration $registration): VfuRegistration
    {
        if (! in_array($registration->stato, [VfuStato::Smontato, VfuStato::Bonificato], true)) {
            throw ValidationException::withMessages([
                'stato' => 'La pratica può essere chiusa solo da stato Bonificato o Smontato.',
            ]);
        }

        $registration->forceFill([
            'stato'        => VfuStato::Rottamato,
            'rottamato_at' => now(),
        ])->save();

        $this->logger->info('vfu', 'vfu.rottamato', 'Pratica VFU chiusa — rottamazione', [
            'entity_type' => 'vfu_registration',
            'entity_id'   => $registration->id,
            'extra'       => [
                'targa'  => $registration->targa,
                'telaio' => $registration->telaio,
            ],
        ]);

        $this->notifications->notifyInApp(
            NotificationEvent::VfuRottamato,
            "Pratica chiusa: {$registration->targa}",
            null,
            'Veicolo segnato come rottamato',
            route('segreteria.vfu.show', $registration),
            [
                'vfu_id' => $registration->id,
                'targa'  => $registration->targa,
                'telaio' => $registration->telaio,
            ],
        );

        $this->vfuNotifications->notifyProprietario($registration->fresh());

        $fresh = $registration->fresh(['documents', 'agenzia']);
        $this->auditVfu('VFU rottamato', $fresh);

        return $fresh;
    }

    public function assegnaOperatore(VfuRegistration $registration, User $operatore): VfuRegistration
    {
        $registration->forceFill([
            'operatore_assegnato_id' => $operatore->id,
        ])->save();

        $fresh = $registration->fresh(['operatoreAssegnato']);
        $title = "Ti è stato assegnato VFU {$fresh->targa}";
        $url = route('operatore.bonifica.wizard', $fresh);

        $this->auditVfu('VFU operatore assegnato', $fresh, [
            'operatore_assegnato_id' => $operatore->id,
            'operatore_nome' => $operatore->name,
        ]);

        $this->notifications->notifyInApp(
            NotificationEvent::VfuOperatoreAssegnato,
            $title,
            $operatore,
            null,
            $url,
            [
                'vfu_id' => $fresh->id,
                'targa'  => $fresh->targa,
            ],
        );

        try {
            $this->webPush->send($operatore, $title, 'Bonifica assegnata', $url);
        } catch (\Throwable) {
            // Push failures must never break core workflow.
        }

        return $fresh;
    }

    public function delete(VfuRegistration $registration): void
    {
        DB::transaction(function () use ($registration) {
            $this->auditVfu('VFU eliminato', $registration);

            foreach ($registration->documents as $doc) {
                $this->documentService->deleteFile($doc);
            }
            $registration->documents()->delete();
            $registration->delete();
        });
    }

    /** @return list<string> */
    public static function csvImportHeaders(): array
    {
        return [
            'targa',
            'telaio',
            'marca',
            'modello',
            'anno',
            'colore',
            'data_accettazione',
            'nome_proprietario',
            'cf_proprietario',
            'email_proprietario',
        ];
    }

    /**
     * @param  list<array<string, string>>  $rows
     * @return array{
     *   imported: int,
     *   errors: list<array{row: int, message: string, data: array<string, string>}>,
     *   total: int
     * }
     */
    public function accettaBatch(array $rows): array
    {
        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $payload = $this->mapImportRow($row);
                $registration = $this->saveDraft(null, $payload);

                if (! empty($payload['_data_accettazione'])) {
                    $registration->forceFill([
                        'data_accettazione' => $payload['_data_accettazione'],
                        'stato'             => VfuStato::InAccettazione,
                    ])->save();
                }

                $imported++;
            } catch (ValidationException $e) {
                $errors[] = [
                    'row'     => $rowNumber,
                    'message' => collect($e->errors())->flatten()->first() ?? 'Validazione fallita.',
                    'data'    => $row,
                ];
            } catch (\Throwable $e) {
                $errors[] = [
                    'row'     => $rowNumber,
                    'message' => $e->getMessage(),
                    'data'    => $row,
                ];
            }
        }

        return [
            'imported' => $imported,
            'errors'   => $errors,
            'total'    => count($rows),
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private function mapImportRow(array $row): array
    {
        $targa = strtoupper(trim($row['targa'] ?? ''));
        $telaio = strtoupper(trim($row['telaio'] ?? ''));

        if ($targa === '' || $telaio === '') {
            throw ValidationException::withMessages([
                'targa' => 'Targa e telaio sono obbligatori.',
            ]);
        }

        $noteParts = [];
        if (filled($row['colore'] ?? null)) {
            $noteParts[] = 'Colore: '.trim($row['colore']);
        }

        $dataConsegna = null;
        if (filled($row['anno'] ?? null) && is_numeric($row['anno'])) {
            $dataConsegna = sprintf('%04d-01-01', (int) $row['anno']);
        }

        $dataAccettazione = null;
        if (filled($row['data_accettazione'] ?? null)) {
            $parsed = $this->parseImportDate($row['data_accettazione']);
            if ($parsed === null) {
                throw ValidationException::withMessages([
                    'data_accettazione' => 'Formato data non valido (usare gg/mm/aaaa o aaaa-mm-gg).',
                ]);
            }
            $dataAccettazione = $parsed;
        }

        $email = trim($row['email_proprietario'] ?? '');
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email_proprietario' => 'Email proprietario non valida.',
            ]);
        }

        return [
            'targa'              => $targa,
            'telaio'             => $telaio,
            'marca'              => trim($row['marca'] ?? ''),
            'modello'            => trim($row['modello'] ?? ''),
            'proprietario'       => trim($row['nome_proprietario'] ?? ''),
            'codice_fiscale'     => strtoupper(trim($row['cf_proprietario'] ?? '')),
            'email_proprietario' => $email ?: null,
            'data_consegna'      => $dataConsegna,
            'note'               => $noteParts !== [] ? implode(' · ', $noteParts) : null,
            '_data_accettazione' => $dataAccettazione,
        ];
    }

    private function parseImportDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                return \Illuminate\Support\Carbon::createFromFormat($format, $value)->toDateString();
            } catch (\Throwable) {
                // try next format
            }
        }

        return null;
    }

    private function auditVfu(string $description, VfuRegistration $registration, array $properties = []): void
    {
        $this->audit->record(
            'vfu',
            $description,
            $registration,
            array_merge([
                'targa'  => $registration->targa,
                'telaio' => $registration->telaio,
                'stato'  => $registration->stato->value,
            ], $properties),
        );
    }

    private function normalizePayload(array $data): array
    {
        $payload = Arr::only($data, [
            'tipo_veicolo',
            'nazione',
            'targa',
            'telaio',
            'codice_motore',
            'marca',
            'modello',
            'nome',
            'cognome',
            'proprietario',
            'email_proprietario',
            'pec_proprietario',
            'codice_fiscale',
            'regione',
            'indirizzo',
            'comune',
            'provincia',
            'data_nascita',
            'luogo_nascita',
            'nazionalita_proprietario',
            'provincia_nascita',
            'tipo_documento_identita',
            'numero_documento_identita',
            'note_carrozzeria',
            'provenienza_veicolo',
            'targa_estera',
            'targa_estera_valore',
            'peso_kg',
            'note',
        ]);

        unset($payload['_data_accettazione']);

        $payload['tipo_veicolo'] = $payload['tipo_veicolo'] ?? 'Autovettura';
        $payload['nazione'] = $payload['nazione'] ?? 'Italia';
        $payload['targa'] = strtoupper(trim($payload['targa'] ?? ''));
        $payload['telaio'] = strtoupper(trim($payload['telaio'] ?? ''));
        $payload['codice_motore'] = trim($payload['codice_motore'] ?? '') ?: null;
        $payload['marca'] = $payload['marca'] ?? '';
        $payload['modello'] = $payload['modello'] ?? '';
        $payload['peso_kg'] = (float) ($payload['peso_kg'] ?? 0);
        $payload['data_consegna'] = $data['data_consegna'] ?? now()->toDateString();
        $payload['proprietario'] = $payload['proprietario']
            ?? trim(($payload['nome'] ?? '').' '.($payload['cognome'] ?? ''));
        $payload['nazionalita_proprietario'] = $payload['nazionalita_proprietario'] ?? 'Italiana';
        $payload['provincia_nascita'] = strtoupper(trim($payload['provincia_nascita'] ?? '')) ?: null;
        $payload['targa_estera'] = (bool) ($payload['targa_estera'] ?? false);
        $payload['targa_estera_valore'] = $payload['targa_estera']
            ? strtoupper(trim($payload['targa_estera_valore'] ?? '')) ?: null
            : null;

        return $payload;
    }
}
