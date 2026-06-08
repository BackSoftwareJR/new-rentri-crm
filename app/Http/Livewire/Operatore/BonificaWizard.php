<?php

namespace App\Http\Livewire\Operatore;

use App\Domain\Bonifica\BonificaMovimentoService;
use App\Domain\Bonifica\BonificaPericolosiChecklistService;
use App\Domain\Bonifica\BonificaService;
use App\Models\CodiceCer;
use App\Models\VfuRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Wizard bonifica')]
class BonificaWizard extends OperatorePage
{
  use AuthorizesRequests;

  public VfuRegistration $vfu;

  public ?int $bonificaId = null;

  /** @var array<int, float> */
  public array $quantita = [];

  /** @var array<string, bool> */
  public array $checklist = [];

  public bool $pericolosiCompletata = false;

  public int $step = 1;

  public ?string $errorMessage = null;

  public bool $success = false;

  public function mount(VfuRegistration $vfu, BonificaService $bonifica): void
  {
    $this->authorize('bonifica.perform', $vfu);

    $this->vfu = $vfu;
    $bonificaSession = $bonifica->startBonifica($vfu);
    $this->bonificaId = $bonificaSession->id;
    $this->pericolosiCompletata = $vfu->bonifica_pericolosi_completata_at !== null;
    $this->checklist = (array) ($bonificaSession->checklist_pericolosi ?? []);

    foreach ($bonificaSession->movimenti as $mov) {
      $this->quantita[$mov->codice_cer_id] = (float) $mov->quantita;
    }

    $this->step = $this->pericolosiCompletata ? 2 : 1;
  }

  public function incrementQty(int $cerId): void
  {
    $current = (float) ($this->quantita[$cerId] ?? 0);
    $this->setQty($cerId, round($current + 0.5, 2));
  }

  public function decrementQty(int $cerId): void
  {
    $current = (float) ($this->quantita[$cerId] ?? 0);
    $this->setQty($cerId, max(0, round($current - 0.5, 2)));
  }

  public function setQty(int $cerId, float $value): void
  {
    $this->quantita[$cerId] = $value;
  }

  public function updatedChecklist(BonificaService $bonifica): void
  {
    $this->persistChecklist($bonifica);
  }

  public function saveDraft(BonificaService $bonifica, BonificaMovimentoService $movimenti): void
  {
    $this->authorize('bonifica.perform', $this->vfu);

    $this->errorMessage = null;

    try {
      $session = $bonifica->startBonifica($this->vfu->fresh());
      $bonifica->saveMovimenti($session, $this->buildMovimentiPayload($movimenti));
      $this->persistChecklist($bonifica, $session);
      session()->flash('success', 'Bozza salvata.');
    } catch (\Throwable $e) {
      $this->errorMessage = $e->getMessage();
    }
  }

  public function confirmPericolosi(BonificaService $bonifica, BonificaMovimentoService $movimenti): void
  {
    $this->authorize('bonifica.advancePericolosi', $this->vfu);

    $this->errorMessage = null;

    try {
      $session = $bonifica->startBonifica($this->vfu->fresh());
      $bonifica->saveMovimenti($session, $this->buildMovimentiPayload($movimenti));
      $session = $this->persistChecklist($bonifica, $session);
      $bonifica->completePericolosi($session);
      $this->vfu->refresh();
      $this->pericolosiCompletata = true;
      $this->step = 2;
      session()->flash('success', 'Fase pericolosi completata: carichi registrati in magazzino.');
    } catch (\Throwable $e) {
      $this->errorMessage = $e->getMessage();
    }
  }

  public function confirmBonifica(BonificaService $bonifica, BonificaMovimentoService $movimenti): void
  {
    $this->authorize('bonifica.perform', $this->vfu);

    $this->errorMessage = null;

    try {
      $session = $bonifica->startBonifica($this->vfu->fresh());
      $bonifica->saveMovimenti($session, $this->buildMovimentiPayload($movimenti));
      $bonifica->completeBonifica($session);
      $this->success = true;
    } catch (\Throwable $e) {
      $this->errorMessage = $e->getMessage();
    }
  }

  public function render(BonificaService $bonifica, BonificaPericolosiChecklistService $checklistService): View
  {
    $codici = $bonifica->codiciCerAttivi();
    $cerPericolosi = $codici->where('categoria', 'pericoloso');
    $cerAltri = $codici->where('categoria', '!=', 'pericoloso');
    $enriched = $bonifica->enrichVeicolo($this->vfu);
    $bonificaSession = $enriched['bonifica_in_corso'];
    $checklistSteps = $bonificaSession
      ? $checklistService->steps($bonificaSession, $this->checklist)
      : [];
    $checklistSummary = $bonificaSession
      ? $checklistService->summary($bonificaSession, $this->checklist)
      : ['done' => 0, 'total' => 0, 'complete' => true];

    return $this->operatoreView(
      'livewire.operatore.bonifica-wizard',
      compact('codici', 'cerPericolosi', 'cerAltri', 'enriched', 'checklistSteps', 'checklistSummary'),
      'bonifica',
      $this->vfu->targa
    );
  }

  /**
   * @return list<array{codice_cer_id: int, quantita: float, um: string, peso_kg: float}>
   */
  private function buildMovimentiPayload(BonificaMovimentoService $movimenti): array
  {
    return CodiceCer::query()
      ->where('attivo', true)
      ->get()
      ->map(function (CodiceCer $cer) use ($movimenti) {
        $q = (float) ($this->quantita[$cer->id] ?? 0);

        return [
          'codice_cer_id' => $cer->id,
          'quantita'      => $q,
          'um'            => $cer->um,
          'peso_kg'       => $movimenti->calcPesoKg($cer, $q),
        ];
      })
      ->all();
  }

  private function persistChecklist(BonificaService $bonifica, ?\App\Models\BonificaVfu $session = null): \App\Models\BonificaVfu
  {
    $this->authorize('bonifica.saveChecklist', $this->vfu);

    $session ??= $bonifica->startBonifica($this->vfu->fresh());

    return $bonifica->saveChecklistPericolosi($session, $this->checklist);
  }
}
