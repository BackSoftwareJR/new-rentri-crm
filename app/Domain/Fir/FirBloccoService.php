<?php

namespace App\Domain\Fir;

use App\Models\FirBlocco;
use App\Models\RentriSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FirBloccoService
{
    /**
     * @return Collection<int, FirBlocco>
     */
    public function list(): Collection
    {
        return FirBlocco::query()
            ->withCount('firs')
            ->orderBy('num_iscr_sito')
            ->orderBy('codice_blocco')
            ->get();
    }

    public function create(string $codiceBlocco, ?string $numIscrSito = null): FirBlocco
    {
        $codiceBlocco = trim($codiceBlocco);
        $numIscrSito = trim($numIscrSito ?? RentriSetting::instance()->num_iscr_sito ?? '');

        if ($codiceBlocco === '') {
            throw new \InvalidArgumentException('Inserire un codice blocco.');
        }

        if ($numIscrSito === '') {
            throw new \InvalidArgumentException('Configurare il numero iscrizione sito RENTRI prima di creare un blocco FIR.');
        }

        if (FirBlocco::query()->where('codice_blocco', $codiceBlocco)->where('num_iscr_sito', $numIscrSito)->exists()) {
            throw new \InvalidArgumentException('Esiste già un blocco con questo codice per il sito.');
        }

        return FirBlocco::create([
            'codice_blocco'      => $codiceBlocco,
            'num_iscr_sito'      => $numIscrSito,
            'progressivo_ultimo' => 0,
        ]);
    }

    public function conteggioDisponibile(FirBlocco $blocco): int
    {
        return $blocco->progressiviRimanenti();
    }

    public function isEsaurito(FirBlocco $blocco): bool
    {
        return $blocco->isEsaurito();
    }

    public function assertDisponibilePerVidima(FirBlocco $blocco): void
    {
        if ($this->isEsaurito($blocco)) {
            throw new \RuntimeException(sprintf(
                'Blocco FIR «%s» esaurito: progressivo %d/%d. Sincronizza o crea un nuovo blocco RENTRI.',
                $blocco->codice_blocco,
                $blocco->progressivo_ultimo,
                FirBlocco::progressivoMax(),
            ));
        }
    }
}
