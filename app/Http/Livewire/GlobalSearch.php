<?php

namespace App\Http\Livewire;

use App\Enums\TrasportoStato;
use App\Enums\VfuStato;
use App\Models\Anagrafica;
use App\Models\Fattura;
use App\Models\Fir;
use App\Models\Trasporto;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalSearch extends Component
{
    use AuthorizesRequests;

    public bool $open = false;

    public string $query = '';

    /** @var list<array<string, mixed>> */
    public array $results = [];

    public int $selectedIndex = 0;

    #[On('open-global-search')]
    public function openSearch(): void
    {
        $this->open = true;
        $this->selectedIndex = 0;
        $this->dispatch('global-search-opened');
    }

    public function close(): void
    {
        $this->open = false;
        $this->query = '';
        $this->results = [];
        $this->selectedIndex = 0;
    }

    public function updatedQuery(): void
    {
        $this->selectedIndex = 0;
        $this->results = $this->search(trim($this->query));
    }

    public function selectResult(int $index): void
    {
        $flat = $this->flatResults();

        if (! isset($flat[$index])) {
            return;
        }

        $this->redirect($flat[$index]['url'], navigate: true);
        $this->close();
    }

    public function moveSelection(int $delta): void
    {
        $flat = $this->flatResults();
        $count = count($flat);

        if ($count === 0) {
            return;
        }

        $this->selectedIndex = ($this->selectedIndex + $delta + $count) % $count;
    }

    public function render(): View
    {
        return view('livewire.global-search', [
            'flatResults' => $this->flatResults(),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function search(string $term): array
    {
        if (mb_strlen($term) < 2) {
            return [];
        }

        /** @var User|null $user */
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        $groups = [];

        if ($this->canSearchVfu($user)) {
            $groups[] = $this->searchVfu($term, $user);
        }

        if (Gate::forUser($user)->allows('viewAny', Anagrafica::class)) {
            $groups[] = $this->searchAnagrafiche($term);
        }

        if (Gate::forUser($user)->allows('viewAny', Fattura::class)) {
            $groups[] = $this->searchFatture($term);
        }

        if (Gate::forUser($user)->allows('viewAny', Trasporto::class)) {
            $groups[] = $this->searchTrasporti($term);
        }

        if (Gate::forUser($user)->allows('viewAny', Fir::class)) {
            $groups[] = $this->searchFir($term);
        }

        return array_values(array_filter($groups, fn (array $group): bool => $group['items'] !== []));
    }

    private function canSearchVfu(User $user): bool
    {
        if (Gate::forUser($user)->allows('viewAny', VfuRegistration::class)) {
            return true;
        }

        return $user->hasRole('operatore');
    }

    /**
     * @return array<string, mixed>
     */
    private function searchVfu(string $term, User $user): array
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query = VfuRegistration::query()
            ->forActiveSito()
            ->where(function (Builder $builder) use ($like): void {
                $builder->where('targa', 'like', $like)
                    ->orWhere('telaio', 'like', $like);
            });

        if (! Gate::forUser($user)->allows('viewAny', VfuRegistration::class)) {
            $query->whereIn('stato', [
                VfuStato::Accettato->value,
                VfuStato::AttesaBonifica->value,
                VfuStato::InBonifica->value,
                VfuStato::Bonificato->value,
                VfuStato::InSmontaggio->value,
            ]);
        }

        $items = $query
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'targa', 'telaio', 'marca', 'modello', 'stato'])
            ->map(function (VfuRegistration $vfu) use ($user): array {
                $label = $vfu->targa ?: ($vfu->telaio ?: 'VFU #'.$vfu->id);
                $subtitle = trim(($vfu->marca ?? '').' '.($vfu->modello ?? '')) ?: ($vfu->stato?->label() ?? '');

                return [
                    'id'       => $vfu->id,
                    'label'    => $label,
                    'subtitle' => $subtitle,
                    'url'      => $this->vfuUrl($vfu, $user),
                ];
            })
            ->all();

        return [
            'type'  => 'vfu',
            'label' => 'VFU',
            'icon'  => 'car',
            'items' => $items,
        ];
    }

    private function vfuUrl(VfuRegistration $vfu, User $user): string
    {
        if ($user->hasRole('operatore') && ! $user->hasRole(['admin', 'editor', 'segreteria'])) {
            return match ($vfu->stato) {
                VfuStato::Bonificato, VfuStato::InSmontaggio => route('operatore.smontaggio.wizard', $vfu),
                default => route('operatore.bonifica.wizard', $vfu),
            };
        }

        return route('segreteria.vfu.show', $vfu);
    }

    /**
     * @return array<string, mixed>
     */
    private function searchAnagrafiche(string $term): array
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $items = Anagrafica::query()
            ->where(function (Builder $builder) use ($like): void {
                $builder->where('ragione_sociale', 'like', $like)
                    ->orWhere('codice_fiscale', 'like', $like)
                    ->orWhere('piva', 'like', $like);
            })
            ->orderBy('ragione_sociale')
            ->limit(5)
            ->get(['id', 'ragione_sociale', 'tipo', 'piva'])
            ->map(fn (Anagrafica $anagrafica): array => [
                'id'       => $anagrafica->id,
                'label'    => $anagrafica->ragione_sociale,
                'subtitle' => $anagrafica->piva ?: ucfirst(str_replace('_', ' ', $anagrafica->tipo)),
                'url'      => route('segreteria.anagrafiche.show', $anagrafica),
            ])
            ->all();

        return [
            'type'  => 'anagrafiche',
            'label' => 'Anagrafiche',
            'icon'  => 'users',
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function searchFatture(string $term): array
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $items = Fattura::query()
            ->forActiveSito()
            ->where('numero_fattura', 'like', $like)
            ->orderByDesc('data_emissione')
            ->limit(5)
            ->get(['id', 'numero_fattura', 'stato', 'totale'])
            ->map(fn (Fattura $fattura): array => [
                'id'       => $fattura->id,
                'label'    => $fattura->numero_fattura,
                'subtitle' => $fattura->statoLabel().' · € '.number_format((float) $fattura->totale, 2, ',', '.'),
                'url'      => route('segreteria.fatture.show', $fattura),
            ])
            ->all();

        return [
            'type'  => 'fatture',
            'label' => 'Fatture',
            'icon'  => 'file-text',
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function searchTrasporti(string $term): array
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $items = Trasporto::query()
            ->forActiveSito()
            ->with(['codiceCer:id,codice', 'destinatario:id,ragione_sociale'])
            ->where(function (Builder $builder) use ($like, $term): void {
                $builder->where('note', 'like', $like)
                    ->orWhereHas('codiceCer', fn (Builder $cer) => $cer->where('codice', 'like', $like))
                    ->orWhereHas('destinatario', fn (Builder $a) => $a->where('ragione_sociale', 'like', $like));

                if (ctype_digit($term)) {
                    $builder->orWhere('id', (int) $term);
                }
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function (Trasporto $trasporto): array {
                $stato = $trasporto->stato instanceof TrasportoStato
                    ? $trasporto->stato->value
                    : (string) $trasporto->stato;

                return [
                    'id'       => $trasporto->id,
                    'label'    => 'Trasporto #'.$trasporto->id,
                    'subtitle' => trim(($trasporto->codiceCer?->codice ?? '').' · '.($trasporto->destinatario?->ragione_sociale ?? '').' · '.$stato),
                    'url'      => route('segreteria.trasporti.show', $trasporto),
                ];
            })
            ->all();

        return [
            'type'  => 'trasporti',
            'label' => 'Trasporti',
            'icon'  => 'truck',
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function searchFir(string $term): array
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $items = Fir::query()
            ->where('numero_fir', 'like', $like)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'numero_fir', 'stato'])
            ->map(fn (Fir $fir): array => [
                'id'       => $fir->id,
                'label'    => $fir->numero_fir,
                'subtitle' => is_object($fir->stato) ? $fir->stato->value : (string) $fir->stato,
                'url'      => route('segreteria.fir', ['q' => $fir->numero_fir]),
            ])
            ->all();

        return [
            'type'  => 'fir',
            'label' => 'FIR',
            'icon'  => 'clipboard',
            'items' => $items,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function flatResults(): array
    {
        $flat = [];

        foreach ($this->results as $group) {
            foreach ($group['items'] as $item) {
                $flat[] = array_merge($item, [
                    'type'       => $group['type'],
                    'groupLabel' => $group['label'],
                ]);
            }
        }

        return $flat;
    }
}
