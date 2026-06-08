<div>
    @include('livewire.partials.flash-messages')

    @php
        $status = $compliance->anagraficaComplianceStatus($anagrafica);
        $statusVariant = match ($status) {
            'valida' => 'success',
            'in_scadenza' => 'warning',
            'non_conforme' => 'danger',
            default => 'muted',
        };
        $statusLabel = match ($status) {
            'valida' => 'Autorizzazione valida',
            'in_scadenza' => 'Autorizzazione in scadenza',
            'non_conforme' => 'Non conforme',
            default => 'Autorizzazione non richiesta',
        };
        $showAuthAlert = in_array($status, ['in_scadenza', 'non_conforme'], true);
    @endphp

    @if ($showAuthAlert)
        <div class="seg-card seg-card-padding-sm seg-alert-banner" role="alert">
            @if ($status === 'in_scadenza')
                Attenzione: almeno un'autorizzazione scade entro {{ \App\Domain\Anagrafiche\AuthorizationComplianceService::EXPIRY_WARNING_DAYS }} giorni.
            @else
                Attenzione: nessuna autorizzazione valida — verificare scadenze e rinnovi.
            @endif
        </div>
    @endif

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <h1>{{ $anagrafica->ragione_sociale }}</h1>
            <p>
                <x-badge-stato :stato="$statusVariant" :label="$statusLabel" />
                <span class="seg-muted-inline">{{ $anagrafica->tipoLabel() }}</span>
            </p>
        </div>
        <div class="seg-header-actions">
            <a href="{{ route('segreteria.anagrafiche.edit', $anagrafica) }}" class="seg-btn seg-btn-primary" wire:navigate>Modifica</a>
            <a href="{{ route('segreteria.anagrafiche') }}" class="seg-btn seg-btn-secondary" wire:navigate>Elenco</a>
        </div>
    </div>

    <div class="seg-detail-grid">
        <div class="seg-card seg-card-padding">
            <h2 class="seg-section-title">Anagrafica</h2>
            <dl class="seg-dl">
                <dt>P.IVA</dt><dd>{{ $anagrafica->piva ?: '—' }}</dd>
                <dt>Codice fiscale</dt><dd>{{ $anagrafica->codice_fiscale ?: '—' }}</dd>
                <dt>Codice SDI</dt><dd>{{ $anagrafica->codice_sdi ?: '—' }}</dd>
                <dt>Email</dt><dd>{{ $anagrafica->email ?: '—' }}</dd>
                <dt>PEC</dt><dd>{{ $anagrafica->pec ?: '—' }}</dd>
                <dt>Telefono</dt><dd>{{ $anagrafica->telefono ?: '—' }}</dd>
                <dt>Indirizzo</dt>
                <dd>
                    @if ($anagrafica->indirizzo)
                        {{ $anagrafica->indirizzo }}, {{ $anagrafica->cap }} {{ $anagrafica->citta }} ({{ $anagrafica->provincia }})
                    @else
                        —
                    @endif
                </dd>
                @if ($anagrafica->tipo === 'impianto')
                    <dt>Trasporti</dt><dd>{{ $anagrafica->gestisce_trasporti ? 'Sì' : 'No' }}</dd>
                @endif
                <dt>Note</dt><dd>{{ $anagrafica->note ?: '—' }}</dd>
            </dl>

            @if ($this->canVerificaRentri())
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--seg-border, #e5e7eb);">
                    <h3 class="seg-section-title" style="font-size: 0.9rem; margin-bottom: 0.5rem;">Verifica RENTRI</h3>

                    @if ($anagrafica->rentri_verificato_at)
                        <p style="font-size: 0.85rem; margin: 0 0 0.5rem;">
                            @if ($anagrafica->rentri_verificato_esito === 'iscritto')
                                <span class="seg-badge seg-badge-success">✅ Iscritto RENTRI</span>
                                @if ($anagrafica->rentri_iscrizione_numero)
                                    <span class="seg-text-muted">N. aut. {{ $anagrafica->rentri_iscrizione_numero }}</span>
                                @endif
                            @else
                                <span class="seg-badge seg-badge-warning">⚠️ Non trovato su RENTRI</span>
                            @endif
                            <span class="seg-text-muted" style="font-size: 0.75rem; margin-left: 0.5rem;">
                                Verificato {{ $anagrafica->rentri_verificato_at->diffForHumans() }}
                            </span>
                        </p>
                    @endif

                    @if ($rentriVerificaResult && isset($rentriVerificaResult['error']))
                        <p class="seg-field-error">{{ $rentriVerificaResult['error'] }}</p>
                    @endif

                    <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm"
                        wire:click="verificaRentri"
                        wire:loading.attr="disabled"
                        wire:target="verificaRentri">
                        <span wire:loading.remove wire:target="verificaRentri">
                            {{ $anagrafica->rentri_verificato_at ? 'Ri-verifica su RENTRI' : 'Verifica su RENTRI' }}
                        </span>
                        <span wire:loading wire:target="verificaRentri">Verifica in corso…</span>
                    </button>
                </div>
            @endif
        </div>

        <div class="seg-card seg-card-padding">
            <h2 class="seg-section-title">Autorizzazioni</h2>
            @if ($anagrafica->authorizations->isEmpty())
                <p class="seg-muted">Nessuna autorizzazione registrata.</p>
            @else
                <div class="seg-table-wrap">
                    <table class="seg-table">
                        <thead>
                            <tr>
                                <th>Numero</th>
                                <th>Rilasciata</th>
                                <th>Scadenza</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($anagrafica->authorizations as $auth)
                                @php
                                    $authStatus = $compliance->authorizationStatus($auth);
                                    $variant = match ($authStatus) {
                                        'valida' => 'success',
                                        'in_scadenza' => 'warning',
                                        'scaduta' => 'danger',
                                        default => 'muted',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $auth->numero }}</td>
                                    <td>{{ $auth->rilasciata_il?->format('d/m/Y') }}</td>
                                    <td>{{ $auth->scade_il?->format('d/m/Y') ?? '—' }}</td>
                                    <td><x-badge-stato :stato="$variant" :label="ucfirst(str_replace('_', ' ', $authStatus))" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <livewire:timeline-widget :subject="$anagrafica" title="Storico anagrafica" wire:key="timeline-anagrafica-{{ $anagrafica->id }}" />
</div>
