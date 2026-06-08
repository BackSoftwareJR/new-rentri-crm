<?php

namespace App\Domain\Vfu;

use App\Domain\Magazzino\MagazzinoService;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\VfuStato;
use App\Models\CodiceCer;
use App\Models\RegistroMovimento;
use App\Models\VfuRegistration;
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
    ) {}

    public function query(array $filters = []): Builder
    {
        $query = VfuRegistration::query()
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
            ->selectRaw('stato, COUNT(*) as total')
            ->groupBy('stato')
            ->pluck('total', 'stato');

        return [
            'bozza' => (int) ($counts[VfuStato::Bozza->value] ?? 0),
            'in_accettazione' => (int) ($counts[VfuStato::InAccettazione->value] ?? 0),
            'accettato' => (int) ($counts[VfuStato::Accettato->value] ?? 0)
                + (int) ($counts[VfuStato::AttesaBonifica->value] ?? 0),
            'in_bonifica' => (int) ($counts[VfuStato::InBonifica->value] ?? 0),
            'totale' => (int) VfuRegistration::count(),
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

            return $existing->fresh('documents');
        }

        $byTelaio = ! empty($payload['telaio'])
            ? VfuRegistration::where('telaio', $payload['telaio'])->first()
            : null;
        $byTarga = ! empty($payload['targa'])
            ? VfuRegistration::where('targa', $payload['targa'])->first()
            : null;

        if ($byTelaio) {
            $byTelaio->update($payload);

            return $byTelaio->fresh('documents');
        }

        if ($byTarga) {
            $byTarga->update($payload);

            return $byTarga->fresh('documents');
        }

        $payload['stato'] = VfuStato::Bozza;

        return VfuRegistration::create($payload);
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

            return $registration->fresh(['documents', 'registroMovimenti']);
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

    public function inviaAgenziaStub(VfuRegistration $registration, int $anagraficaId): VfuRegistration
    {
        $registration->forceFill([
            'stato'                 => VfuStato::InviatoAgenzia,
            'agenzia_anagrafica_id' => $anagraficaId,
            'data_invio_agenzia'    => now(),
        ])->save();

        return $registration->fresh(['agenzia', 'documents']);
    }

    public function delete(VfuRegistration $registration): void
    {
        DB::transaction(function () use ($registration) {
            foreach ($registration->documents as $doc) {
                $this->documentService->deleteFile($doc);
            }
            $registration->documents()->delete();
            $registration->delete();
        });
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
            'codice_fiscale',
            'regione',
            'indirizzo',
            'comune',
            'provincia',
            'data_nascita',
            'luogo_nascita',
            'peso_kg',
            'note',
        ]);

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

        return $payload;
    }
}
