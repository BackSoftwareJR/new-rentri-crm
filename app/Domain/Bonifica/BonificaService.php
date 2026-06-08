<?php

namespace App\Domain\Bonifica;

use App\Enums\VfuStato;
use App\Models\BonificaVfu;
use App\Models\CodiceCer;
use App\Models\VfuRegistration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BonificaService
{
  public const PERICOLOSI_DEADLINE_DAYS = 30;

  public function __construct(
    private readonly BonificaMovimentoService $movimenti,
    private readonly BonificaNotificationService $notifications,
    private readonly BonificaPericolosiChecklistService $checklist,
  ) {}

  /**
   * @param  array{search?: string, filtro?: string}  $filters
   */
  public function queryVeicoliDaBonificare(array $filters = []): Builder
  {
    $query = VfuRegistration::query()
      ->whereIn('stato', [VfuStato::Accettato, VfuStato::AttesaBonifica, VfuStato::InBonifica])
      ->orderByRaw('CASE WHEN stato = ? THEN 0 ELSE 1 END', [VfuStato::InBonifica->value])
      ->orderByDesc('data_accettazione');

    if (! empty($filters['search'])) {
      $term = '%'.trim($filters['search']).'%';
      $query->where(function (Builder $q) use ($term) {
        $q->where('targa', 'like', $term)
          ->orWhere('telaio', 'like', $term)
          ->orWhere('codice_motore', 'like', $term)
          ->orWhere('marca', 'like', $term)
          ->orWhere('modello', 'like', $term);
      });
    }

    $filtro = $filters['filtro'] ?? 'tutti';

    if ($filtro === 'scaduti') {
      $query->whereNull('bonifica_pericolosi_completata_at')
        ->whereNotNull('data_accettazione')
        ->whereDate('data_accettazione', '<', now()->subDays(self::PERICOLOSI_DEADLINE_DAYS)->toDateString());
    } elseif ($filtro === 'in_tempo') {
      $query->whereNull('bonifica_pericolosi_completata_at')
        ->whereNotNull('data_accettazione')
        ->whereDate('data_accettazione', '>=', now()->subDays(self::PERICOLOSI_DEADLINE_DAYS)->toDateString());
    } elseif ($filtro === 'dopo_pericolosi') {
      $query->whereNotNull('bonifica_pericolosi_completata_at');
    }

    return $query;
  }

  public function enrichVeicolo(VfuRegistration $vfu): array
  {
    $bonifica = BonificaVfu::query()
      ->where('vfu_registration_id', $vfu->id)
      ->where('stato', 'in_corso')
      ->with('movimenti.codiceCer')
      ->latest()
      ->first();

    $deadline = $this->pericolosiDeadline($vfu);
    $giorni = $deadline ? (int) Carbon::today()->diffInDays($deadline, false) : null;
    $pericolosiOk = $vfu->bonifica_pericolosi_completata_at !== null;

    $fase = $pericolosiOk
      ? 'pericolosi_ok'
      : ($deadline && Carbon::today()->gt($deadline) ? 'scaduto' : 'in_tempo');

    return [
      'vfu'                          => $vfu,
      'bonifica_in_corso'            => $bonifica,
      'bonifica_pericolosi_deadline' => $deadline?->toDateString(),
      'bonifica_giorni_alla_scadenza' => $giorni,
      'bonifica_fase'                => $fase,
      'fase_corrente'                => $this->faseCorrente($vfu),
      'checklist_pericolosi'         => $bonifica && ! $pericolosiOk
        ? $this->checklist->summary($bonifica, (array) ($bonifica->checklist_pericolosi ?? []))
        : null,
    ];
  }

  public function startBonifica(VfuRegistration $vfu): BonificaVfu
  {
    if (! in_array($vfu->stato, [VfuStato::Accettato, VfuStato::AttesaBonifica, VfuStato::InBonifica], true)) {
      throw new \InvalidArgumentException('Il veicolo non è in stato bonificabile.');
    }

    return DB::transaction(function () use ($vfu) {
      $bonifica = BonificaVfu::query()
        ->where('vfu_registration_id', $vfu->id)
        ->where('stato', 'in_corso')
        ->latest()
        ->first();

      if (! $bonifica) {
        $bonifica = BonificaVfu::create([
          'vfu_registration_id' => $vfu->id,
          'stato'               => 'in_corso',
          'fase'                => $this->faseCorrente($vfu),
          'data_inizio'         => now(),
        ]);
      }

      if (in_array($vfu->stato, [VfuStato::Accettato, VfuStato::AttesaBonifica], true)) {
        $vfu->update(['stato' => VfuStato::InBonifica]);
      }

      return $bonifica->fresh(['movimenti.codiceCer', 'vfuRegistration']);
    });
  }

  /**
   * @param  array<int, array{codice_cer_id: int, quantita: float|string, um?: string, peso_kg?: float|string}>  $rows
   */
  public function saveMovimenti(BonificaVfu $bonifica, array $rows): BonificaVfu
  {
    $normalized = collect($rows)->map(function (array $row) {
      $cer = CodiceCer::findOrFail($row['codice_cer_id']);
      $quantita = (float) ($row['quantita'] ?? 0);
      $um = $row['um'] ?? $cer->um;
      $peso = isset($row['peso_kg']) ? (float) $row['peso_kg'] : $this->movimenti->calcPesoKg($cer, $quantita);

      return [
        'codice_cer_id' => $cer->id,
        'quantita'      => $quantita,
        'um'            => $um,
        'peso_kg'       => $peso,
      ];
    })->all();

    $this->movimenti->syncMovimenti($bonifica, $normalized);

    return $bonifica->fresh(['movimenti.codiceCer', 'vfuRegistration']);
  }

  /**
   * @param  array<string, bool>  $checklist
   */
  public function saveChecklistPericolosi(BonificaVfu $bonifica, array $checklist): BonificaVfu
  {
    $normalized = [];
    foreach (BonificaPericolosiChecklistService::MANUAL_STEPS as $key => $label) {
      $normalized[$key] = ! empty($checklist[$key]);
    }

    $bonifica->update(['checklist_pericolosi' => $normalized]);

    return $bonifica->fresh(['movimenti.codiceCer', 'vfuRegistration']);
  }

  public function completePericolosi(BonificaVfu $bonifica): BonificaVfu
  {
    $bonifica->loadMissing(['movimenti.codiceCer', 'vfuRegistration']);
    $vfu = $bonifica->vfuRegistration;

    if ($vfu->bonifica_pericolosi_completata_at) {
      throw new \InvalidArgumentException('La fase pericolosi è già stata completata per questo veicolo.');
    }

    $this->assertPericolosiMovimentiPresenti($bonifica);

    $checklist = (array) ($bonifica->checklist_pericolosi ?? []);
    if (! $this->checklist->canAdvance($bonifica, $checklist)) {
      throw new \InvalidArgumentException(
        'Checklist pericolosi incompleta: '.implode(' ', $this->checklist->blockers($bonifica, $checklist))
      );
    }

    DB::transaction(function () use ($bonifica, $vfu) {
      $this->movimenti->registerCarichi($bonifica, BonificaMovimentoService::FILTER_PERICOLOSO);

      $vfu->forceFill(['bonifica_pericolosi_completata_at' => now()])->save();
      $bonifica->update(['fase' => 'altri']);
    });

    $bonifica = $bonifica->fresh(['movimenti.codiceCer', 'vfuRegistration']);
    $deadline = $this->pericolosiDeadline($bonifica->vfuRegistration);
    $withinDeadline = $deadline === null || Carbon::today()->lte($deadline);
    $this->notifications->notifyPericolosiCompletata($bonifica->vfuRegistration, $deadline, $withinDeadline);

    return $bonifica;
  }

  public function completeBonifica(BonificaVfu $bonifica): BonificaVfu
  {
    $bonifica->loadMissing(['movimenti.codiceCer', 'vfuRegistration']);
    $vfu = $bonifica->vfuRegistration;

    if ($this->hasPericolosiAttivi() && ! $vfu->bonifica_pericolosi_completata_at) {
      throw new \InvalidArgumentException('Completa prima la bonifica dei liquidi e rifiuti pericolosi.');
    }

    $this->assertPericolosiMovimentiPresenti($bonifica);

    $filter = $vfu->bonifica_pericolosi_completata_at
      ? BonificaMovimentoService::FILTER_NON_PERICOLOSO
      : BonificaMovimentoService::FILTER_TUTTI;

    DB::transaction(function () use ($bonifica, $vfu, $filter) {
      $this->movimenti->registerCarichi($bonifica, $filter);

      $bonifica->update([
        'stato'              => 'completata',
        'data_completamento' => now(),
      ]);

      $vfu->update(['stato' => VfuStato::Bonificato]);
    });

    return $bonifica->fresh(['movimenti.codiceCer', 'vfuRegistration']);
  }

  public function faseCorrente(VfuRegistration $vfu): string
  {
    return $vfu->bonifica_pericolosi_completata_at ? 'altri' : 'pericolosi';
  }

  public function pericolosiDeadline(VfuRegistration $vfu): ?Carbon
  {
    if (! $vfu->data_accettazione) {
      return null;
    }

    return Carbon::parse($vfu->data_accettazione)->addDays(self::PERICOLOSI_DEADLINE_DAYS)->startOfDay();
  }

  public function isPericolosiDeadlineActive(VfuRegistration $vfu): bool
  {
    if ($vfu->bonifica_pericolosi_completata_at) {
      return false;
    }

    $deadline = $this->pericolosiDeadline($vfu);

    return $deadline !== null && Carbon::today()->lte($deadline);
  }

  public function codiciCerAttivi(): Collection
  {
    return CodiceCer::query()
      ->where('attivo', true)
      ->orderBy('categoria')
      ->orderBy('codice')
      ->get();
  }

  public function hasPericolosiAttivi(): bool
  {
    return CodiceCer::query()->where('categoria', 'pericoloso')->where('attivo', true)->exists();
  }

  private function assertPericolosiMovimentiPresenti(BonificaVfu $bonifica): void
  {
    $pericolosiIds = CodiceCer::query()
      ->where('categoria', 'pericoloso')
      ->where('attivo', true)
      ->pluck('id');

    if ($pericolosiIds->isEmpty()) {
      return;
    }

    $movimenti = $bonifica->movimenti;

    foreach ($pericolosiIds as $pid) {
      if (! $movimenti->firstWhere('codice_cer_id', $pid)) {
        $cer = CodiceCer::find($pid);
        throw new \InvalidArgumentException(
          'Manca il movimento per il codice pericoloso '
          .($cer->codice ?? '#'.$pid).' — '.($cer->descrizione ?? '')
        );
      }
    }
  }
}
