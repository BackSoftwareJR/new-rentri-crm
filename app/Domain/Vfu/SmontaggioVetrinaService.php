<?php

namespace App\Domain\Vfu;

use App\Models\EcommerceProdotto;
use App\Models\SmontaggioRicambio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SmontaggioVetrinaService
{
    /**
     * @param  array{
     *   codice?: string,
     *   categoria?: string,
     *   giacenza?: int,
     *   attivo?: bool,
     * }  $options
     */
    public function pubblicaInVetrina(SmontaggioRicambio $ricambio, array $options = []): EcommerceProdotto
    {
        $ricambio->loadMissing('session.vfuRegistration');

        $codice = $options['codice'] ?? $this->codiceForRicambio($ricambio);

        $prodotto = EcommerceProdotto::query()->updateOrCreate(
            ['codice' => $codice],
            [
                'nome'                => $ricambio->descrizione,
                'descrizione'         => $this->buildDescrizione($ricambio),
                'categoria'           => $options['categoria'] ?? 'ricambi_usati',
                'prezzo'              => (float) ($ricambio->valore_stimato ?? 0),
                'giacenza'            => max(0, (int) ($options['giacenza'] ?? 1)),
                'vfu_registration_id' => $ricambio->session?->vfu_registration_id,
                'attivo'              => (bool) ($options['attivo'] ?? false),
            ],
        );

        $this->syncImmagine($ricambio, $prodotto);

        return $prodotto->fresh();
    }

    /**
     * @param  Collection<int, SmontaggioRicambio>  $ricambi
     * @return array<int, EcommerceProdotto>
     */
    public function pubblicaBatch(Collection $ricambi, array $options = []): array
    {
        $published = [];

        foreach ($ricambi as $ricambio) {
            $published[$ricambio->id] = $this->pubblicaInVetrina($ricambio, $options);
        }

        return $published;
    }

    private function codiceForRicambio(SmontaggioRicambio $ricambio): string
    {
        if (filled($ricambio->numero_parte)) {
            return 'SMR-'.strtoupper(preg_replace('/[^A-Za-z0-9]+/', '-', $ricambio->numero_parte)).'-'.$ricambio->id;
        }

        return 'SMR-'.$ricambio->id;
    }

    private function buildDescrizione(SmontaggioRicambio $ricambio): string
    {
        $parts = ['Condizione: '.$ricambio->condizioneLabel()];

        if (filled($ricambio->numero_parte)) {
            array_unshift($parts, 'N° parte: '.$ricambio->numero_parte);
        }

        return implode(' · ', $parts);
    }

    private function syncImmagine(SmontaggioRicambio $ricambio, EcommerceProdotto $prodotto): void
    {
        if (blank($ricambio->foto_path) || ! Storage::disk('local')->exists($ricambio->foto_path)) {
            return;
        }

        $ext = pathinfo($ricambio->foto_path, PATHINFO_EXTENSION) ?: 'jpg';
        $destPath = sprintf('ecommerce/prodotti/%d/smontaggio-%d.%s', $prodotto->id, $ricambio->id, $ext);

        if (filled($prodotto->immagine_path)) {
            Storage::disk('public')->delete($prodotto->immagine_path);
        }

        Storage::disk('public')->put($destPath, Storage::disk('local')->get($ricambio->foto_path));
        $prodotto->update(['immagine_path' => $destPath]);
    }
}
