<?php

namespace App\Domain\Vfu;

use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Enums\VfuStato;
use App\Models\SmontaggioRicambio;
use App\Models\SmontaggioSession;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Services\Push\WebPushService;
use App\Support\Logging\StructuredLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class SmontaggioService
{
    public function __construct(
        private readonly StructuredLogService $logger,
        private readonly NotificationService $notifications,
        private readonly WebPushService $webPush,
    ) {}

    /**
     * Avvia o riprende una sessione di smontaggio per il VFU.
     * Il VFU deve essere in stato Bonificato o InSmontaggio.
     */
    public function avvia(VfuRegistration $vfu, User $operatore): SmontaggioSession
    {
        if (! in_array($vfu->stato, [VfuStato::Bonificato, VfuStato::InSmontaggio], true)) {
            throw new \InvalidArgumentException(
                'Il veicolo deve essere bonificato prima di avviare lo smontaggio. Stato attuale: '.$vfu->stato->label()
            );
        }

        return DB::transaction(function () use ($vfu, $operatore) {
            $session = SmontaggioSession::query()
                ->where('vfu_registration_id', $vfu->id)
                ->whereIn('stato', ['avviato', 'in_corso'])
                ->latest()
                ->first();

            if (! $session) {
                $session = SmontaggioSession::create([
                    'vfu_registration_id' => $vfu->id,
                    'operatore_id' => $operatore->id,
                    'stato' => 'avviato',
                    'started_at' => now(),
                ]);
            }

            if ($vfu->stato === VfuStato::Bonificato) {
                $vfu->update(['stato' => VfuStato::InSmontaggio]);
            }

            $this->logger->info('operatore', 'smontaggio.avviato', 'Sessione smontaggio avviata', [
                'entity_type' => 'vfu_registration',
                'entity_id' => $vfu->id,
                'extra' => [
                    'session_id' => $session->id,
                    'operatore_id' => $operatore->id,
                    'targa' => $vfu->targa,
                ],
            ]);

            return $session->fresh(['ricambi']);
        });
    }

    /**
     * Aggiunge un ricambio alla sessione di smontaggio.
     *
     * @param  array{
     *   descrizione: string,
     *   numero_parte?: string|null,
     *   condizione?: string,
     *   valore_stimato?: float|string|null,
     *   foto?: TemporaryUploadedFile|null,
     * }  $data
     */
    public function aggiungiRicambio(SmontaggioSession $session, array $data): SmontaggioRicambio
    {
        if ($session->isCompletata()) {
            throw new \InvalidArgumentException('Impossibile aggiungere ricambi a una sessione completata.');
        }

        $fotoPath = null;
        if (! empty($data['foto'])) {
            $fotoPath = $data['foto']->store('smontaggio/ricambi', 'local');
        }

        $ricambio = SmontaggioRicambio::create([
            'smontaggio_session_id' => $session->id,
            'numero_parte' => $data['numero_parte'] ?? null,
            'descrizione' => $data['descrizione'],
            'condizione' => $data['condizione'] ?? 'buono',
            'valore_stimato' => isset($data['valore_stimato']) && $data['valore_stimato'] !== ''
                ? (float) $data['valore_stimato']
                : null,
            'foto_path' => $fotoPath,
        ]);

        if ($session->stato === 'avviato') {
            $session->update(['stato' => 'in_corso']);
        }

        $this->logger->info('operatore', 'smontaggio.ricambio_aggiunto', 'Ricambio aggiunto a sessione smontaggio', [
            'entity_type' => 'smontaggio_session',
            'entity_id' => $session->id,
            'extra' => [
                'ricambio_id' => $ricambio->id,
                'descrizione' => $ricambio->descrizione,
                'condizione' => $ricambio->condizione,
            ],
        ]);

        return $ricambio;
    }

    /**
     * Rimuove un ricambio dalla sessione, eliminando eventualmente la foto.
     */
    public function rimuoviRicambio(SmontaggioSession $session, int $ricambioId): void
    {
        if ($session->isCompletata()) {
            throw new \InvalidArgumentException('Impossibile rimuovere ricambi da una sessione completata.');
        }

        $ricambio = SmontaggioRicambio::query()
            ->where('smontaggio_session_id', $session->id)
            ->findOrFail($ricambioId);

        if ($ricambio->foto_path) {
            Storage::disk('local')->delete($ricambio->foto_path);
        }

        $ricambio->delete();
    }

    /**
     * Completa la sessione di smontaggio e aggiorna il VFU a Smontato.
     */
    public function completa(SmontaggioSession $session): void
    {
        $session->loadMissing(['vfuRegistration', 'ricambi']);
        $vfu = $session->vfuRegistration;

        if ($session->isCompletata()) {
            throw new \InvalidArgumentException('La sessione di smontaggio è già stata completata.');
        }

        if ($vfu->stato !== VfuStato::InSmontaggio) {
            throw new \InvalidArgumentException(
                'Il veicolo non è in stato di smontaggio. Stato attuale: '.$vfu->stato->label()
            );
        }

        DB::transaction(function () use ($session, $vfu) {
            $session->update([
                'stato' => 'completato',
                'completed_at' => now(),
            ]);

            $vfu->update(['stato' => VfuStato::Smontato]);
        });

        $ricambiCount = $session->ricambi->count();

        $this->logger->info('operatore', 'smontaggio.completato', 'Smontaggio completato', [
            'entity_type' => 'vfu_registration',
            'entity_id' => $vfu->id,
            'extra' => [
                'session_id' => $session->id,
                'targa' => $vfu->targa,
                'ricambi_count' => $ricambiCount,
            ],
        ]);

        $this->notifications->notifyInApp(
            NotificationEvent::SmontaggioCompletato,
            "Smontaggio completato: {$vfu->targa}",
            null,
            "{$ricambiCount} ricamb".($ricambiCount === 1 ? 'io catalogato' : 'i catalogati'),
            route('segreteria.vfu.show', $vfu),
            ['vfu_id' => $vfu->id, 'targa' => $vfu->targa, 'ricambi_count' => $ricambiCount],
        );

        try {
            $this->webPush->sendToRoles(
                ['segreteria', 'admin'],
                "Smontaggio {$vfu->targa} completato",
                "{$ricambiCount} ricamb".($ricambiCount === 1 ? 'io' : 'i').' disponibili',
                route('segreteria.vfu.show', $vfu),
            );
        } catch (\Throwable) {
            // Push failures must never break core workflow.
        }
    }

    /**
     * Restituisce VFU pronti per lo smontaggio (bonificati o già in smontaggio).
     */
    public function queryVeicoliPerSmontaggio(): Builder
    {
        return VfuRegistration::query()
            ->whereIn('stato', [VfuStato::Bonificato, VfuStato::InSmontaggio])
            ->orderByRaw('CASE WHEN stato = ? THEN 0 ELSE 1 END', [VfuStato::InSmontaggio->value])
            ->orderByDesc('updated_at');
    }
}
