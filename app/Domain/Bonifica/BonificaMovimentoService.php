<?php

namespace App\Domain\Bonifica;

use App\Domain\Magazzino\MagazzinoService;
use App\Enums\RegistroMovimentoTipo;
use App\Models\BonificaVfu;
use App\Models\BonificaVfuMovimento;
use App\Models\CodiceCer;
use App\Models\RegistroMovimento;
use Illuminate\Support\Facades\DB;

class BonificaMovimentoService
{
  public const FILTER_PERICOLOSO = 'pericoloso';

  public const FILTER_NON_PERICOLOSO = 'non_pericoloso';

  public const FILTER_TUTTI = 'tutti';

  public function __construct(
    private readonly MagazzinoService $magazzino,
  ) {}

  /**
   * Sostituisce i movimenti bozza della bonifica (con controllo lock RENTRI).
   *
   * @param  array<int, array{codice_cer_id: int, quantita: float|string, um: string, peso_kg: float|string}>  $movimenti
   */
  public function syncMovimenti(BonificaVfu $bonifica, array $movimenti): void
  {
    $lockedCerIds = $this->lockedCodiceCerIds($bonifica);
    $registeredCerIds = $this->registeredCodiceCerIds($bonifica);
    $preserveCerIds = array_values(array_unique(array_merge($lockedCerIds, $registeredCerIds)));

    foreach ($movimenti as $row) {
      $cerId = (int) $row['codice_cer_id'];
      if (in_array($cerId, $lockedCerIds, true)) {
        throw new \InvalidArgumentException(
          'Impossibile modificare quantità già trasmesse al RENTRI (codice CER #'.$cerId.').'
        );
      }
    }

    DB::transaction(function () use ($bonifica, $movimenti, $preserveCerIds) {
      $bonifica->movimenti()
        ->whereNotIn('codice_cer_id', $preserveCerIds)
        ->delete();

      foreach ($movimenti as $row) {
        $cerId = (int) $row['codice_cer_id'];
        if (in_array($cerId, $preserveCerIds, true)) {
          continue;
        }

        BonificaVfuMovimento::create([
          'bonifica_vfu_id' => $bonifica->id,
          'codice_cer_id'   => $cerId,
          'quantita'        => $row['quantita'],
          'um'              => $row['um'],
          'peso_kg'         => $row['peso_kg'],
        ]);
      }
    });
  }

  /**
   * Registra carichi in registro_movimenti + magazzino per i movimenti bonifica.
   *
   * @param  self::FILTER_*  $categoriaFilter
   */
  public function registerCarichi(BonificaVfu $bonifica, string $categoriaFilter): int
  {
    $bonifica->loadMissing(['movimenti.codiceCer', 'vfuRegistration']);
    $targa = $bonifica->vfuRegistration->targa ?? '—';
    $count = 0;

    DB::transaction(function () use ($bonifica, $categoriaFilter, $targa, &$count) {
      foreach ($bonifica->movimenti as $movimento) {
        if ((float) $movimento->quantita <= 0 && (float) $movimento->peso_kg <= 0) {
          continue;
        }

        $cer = $movimento->codiceCer;
        if (! $cer || ! $this->matchesCategoriaFilter($cer, $categoriaFilter)) {
          continue;
        }

        if ($this->movimentoAlreadyRegistered($movimento)) {
          continue;
        }

        RegistroMovimento::create([
          'tipo'           => RegistroMovimentoTipo::Carico,
          'codice_cer_id'  => $cer->id,
          'peso_kg'        => $movimento->peso_kg,
          'data_movimento' => now(),
          'source_type'    => RegistroMovimento::SOURCE_BONIFICA_MOVIMENTO,
          'source_id'      => $movimento->id,
          'note'           => 'Bonifica VFU — Targa: '.$targa,
        ]);

        $this->magazzino->addPeso($cer->id, (float) $movimento->peso_kg);
        $count++;
      }
    });

    return $count;
  }

  /**
   * @return list<int>
   */
  /**
   * @return list<int>
   */
  public function registeredCodiceCerIds(BonificaVfu $bonifica): array
  {
    return BonificaVfuMovimento::query()
      ->where('bonifica_vfu_id', $bonifica->id)
      ->whereIn('id', RegistroMovimento::query()
        ->select('source_id')
        ->where('source_type', RegistroMovimento::SOURCE_BONIFICA_MOVIMENTO))
      ->pluck('codice_cer_id')
      ->unique()
      ->values()
      ->all();
  }

  public function lockedCodiceCerIds(BonificaVfu $bonifica): array
  {
    return BonificaVfuMovimento::query()
      ->where('bonifica_vfu_id', $bonifica->id)
      ->whereIn('id', RegistroMovimento::query()
        ->select('source_id')
        ->where('source_type', RegistroMovimento::SOURCE_BONIFICA_MOVIMENTO)
        ->where('rentri_trasmesso', true))
      ->pluck('codice_cer_id')
      ->unique()
      ->values()
      ->all();
  }

  public function calcPesoKg(CodiceCer $cer, float $quantita): float
  {
    if ($quantita <= 0) {
      return 0;
    }

    return $cer->um === 'litri'
      ? round($quantita * 0.9, 2)
      : round($quantita, 2);
  }

  private function matchesCategoriaFilter(CodiceCer $cer, string $filter): bool
  {
    $isPericoloso = $cer->categoria === 'pericoloso';

    return match ($filter) {
      self::FILTER_PERICOLOSO     => $isPericoloso,
      self::FILTER_NON_PERICOLOSO => ! $isPericoloso,
      self::FILTER_TUTTI          => true,
      default                     => throw new \InvalidArgumentException('Filtro categoria non valido: '.$filter),
    };
  }

  private function movimentoAlreadyRegistered(BonificaVfuMovimento $movimento): bool
  {
    return RegistroMovimento::query()
      ->where('source_type', RegistroMovimento::SOURCE_BONIFICA_MOVIMENTO)
      ->where('source_id', $movimento->id)
      ->exists();
  }
}
