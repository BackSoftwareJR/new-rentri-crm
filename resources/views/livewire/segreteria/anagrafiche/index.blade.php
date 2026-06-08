<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <h1>Anagrafiche</h1>
            <p>Gestione contatti, trasportatori e impianti
                <span class="seg-muted-inline">({{ $anagrafiche->total() }} contatti)</span>
            </p>
        </div>
        <a href="{{ route('segreteria.anagrafiche.create') }}" class="seg-btn seg-btn-primary" wire:navigate>+ Nuovo contatto</a>
    </div>

    @if ($authAlerts['in_scadenza'] > 0 || $authAlerts['scadute'] > 0)
        <div class="seg-card seg-card-padding-sm seg-alert-banner" role="status">
            <strong>Autorizzazioni trasporto:</strong>
            @if ($authAlerts['scadute'] > 0)
                <x-badge-stato stato="danger" :label="$authAlerts['scadute'] . ' scadute'" />
            @endif
            @if ($authAlerts['in_scadenza'] > 0)
                <x-badge-stato stato="warning" :label="$authAlerts['in_scadenza'] . ' in scadenza (≤15 gg)'" />
            @endif
        </div>
    @endif

    <div class="seg-card seg-card-padding-sm seg-filters">
        <div class="seg-filters-row">
            <div class="seg-search-field">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cerca per ragione sociale, P.IVA, SDI, email…" />
            </div>
            <select wire:model.live="tipo" class="seg-select">
                <option value="">Tutti i tipi</option>
                @foreach (\App\Models\Anagrafica::TIPI as $t)
                    <option value="{{ $t }}">{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="seg-card seg-card-padding-none">
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Ragione sociale</th>
                        <th>P.IVA</th>
                        <th>SDI</th>
                        <th>Email</th>
                        <th>Conformità</th>
                        <th class="seg-table-actions">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($anagrafiche as $a)
                        @php
                            $status = $compliance->anagraficaComplianceStatus($a);
                            $hasExpired = $status === 'non_conforme' && $a->authorizations->contains(
                                fn ($auth) => $compliance->authorizationStatus($auth) === 'scaduta'
                            );
                        @endphp
                        <tr wire:key="ana-{{ $a->id }}">
                            <td>
                                @php
                                    $tipoStato = match ($a->tipo) {
                                        'trasportatore' => 'info',
                                        'impianto' => 'success',
                                        'privato' => 'muted',
                                        'agenzia_pratiche' => 'warning',
                                        default => 'muted',
                                    };
                                @endphp
                                <x-badge-stato :stato="$tipoStato" :label="$a->tipoLabel()" />
                            </td>
                            <td class="seg-cell-strong">
                                <a href="{{ route('segreteria.anagrafiche.show', $a) }}" class="seg-link" wire:navigate>{{ $a->ragione_sociale }}</a>
                            </td>
                            <td>{{ $a->piva ?: '—' }}</td>
                            <td>{{ $a->codice_sdi ?: '—' }}</td>
                            <td>{{ $a->email ?: '—' }}</td>
                            <td>
                                @if ($status === 'non_richiesta')
                                    <x-badge-stato stato="muted" label="N/A" />
                                @elseif ($status === 'valida')
                                    <x-badge-stato stato="success" label="Valida" />
                                @elseif ($status === 'in_scadenza')
                                    <x-badge-stato stato="warning" label="In scadenza" />
                                @elseif ($hasExpired)
                                    <x-badge-stato stato="danger" label="Scaduta" />
                                @else
                                    <x-badge-stato stato="danger" label="Non conforme" />
                                @endif
                            </td>
                            <td class="seg-table-actions">
                                <a href="{{ route('segreteria.anagrafiche.edit', $a) }}" class="seg-btn seg-btn-ghost seg-btn-sm" wire:navigate>Modifica</a>
                                <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm seg-btn-danger"
                                    wire:click="delete({{ $a->id }})"
                                    wire:confirm="Eliminare {{ $a->ragione_sociale }}?">
                                    Elimina
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="seg-table-empty">Nessuna anagrafica trovata.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($anagrafiche->hasPages())
            <div class="seg-pagination">{{ $anagrafiche->links() }}</div>
        @endif
    </div>
</div>
