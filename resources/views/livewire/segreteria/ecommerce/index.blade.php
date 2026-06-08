<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <h1>E-commerce ricambi</h1>
            <p>Catalogo con immagini, carrello e checkout sicuro (bonifico, POS, Stripe).</p>
        </div>
        <a href="{{ route('segreteria.ecommerce.carrello') }}" class="seg-btn seg-btn-primary" wire:navigate>
            Carrello @if ($cartCount > 0)({{ $cartCount }})@endif
        </a>
    </div>

    <div class="seg-kpi-grid">
        <x-kpi-card title="Prodotti attivi" :value="(string) $contatori['totale']" />
        <x-kpi-card title="Disponibili" :value="(string) $contatori['disponibili']" valueColor="#16a34a" />
        <x-kpi-card title="Esauriti" :value="(string) $contatori['esauriti']" valueColor="#dc2626" />
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
            <h2 class="mag-section-title" style="margin: 0;">Stripe produzione</h2>
            <x-ecommerce-payment-mode-badge />
            <span class="seg-text-muted" style="font-size: 13px;">
                Switch prod: {{ $stripeSwitch['ready'] ? 'pronto' : 'non pronto' }}
                ({{ $stripeSwitch['ok'] }}/{{ $stripeSwitch['total'] }})
            </span>
        </div>
        <p class="seg-text-muted" style="font-size: 13px; margin: 0 0 12px;">
            CLI: <code>php artisan stripe:production-switch-check --dry-run</code>
            · Runbook: <code>docs/STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md</code>
            · <a href="{{ $stripeSwitch['dashboard_url'] }}" target="_blank" rel="noopener noreferrer">Dashboard Stripe</a>
        </p>
        <ul class="seg-list-check" style="list-style: none; padding: 0; margin: 0 0 12px;">
            @foreach ($stripeChecklist as $item)
                <li style="margin-bottom: 6px; font-size: 13px;">
                    @if ($item['ok'])
                        <span aria-hidden="true">✅</span>
                    @else
                        <span aria-hidden="true">⬜</span>
                    @endif
                    {{ $item['label'] }}
                    @if ($item['optional'])
                        <span class="seg-text-muted">(opz.)</span>
                    @endif
                    @if (! $item['ok'] && $item['hint'])
                        <span class="seg-text-muted"> — {{ $item['hint'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        <details style="font-size: 13px;">
            <summary class="seg-text-muted" style="cursor: pointer;">Rollback stub pagamenti</summary>
            <ol style="margin: 8px 0 0; padding-left: 20px;">
                @foreach ($stripeRollback as $step)
                    <li style="margin-bottom: 4px;"><strong>{{ $step['action'] }}</strong> — {{ $step['detail'] }}</li>
                @endforeach
            </ol>
        </details>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
            <h2 class="mag-section-title" style="margin: 0;">Riconciliazione pagamenti ({{ $reconciliationSummary['days'] }} gg)</h2>
            <button type="button" wire:click="exportReconciliationCsv" class="seg-btn seg-btn-secondary seg-btn-sm">
                Export CSV
            </button>
        </div>
        <div class="seg-kpi-grid" style="margin-bottom: 12px;">
            <x-kpi-card title="Matched" :value="(string) $reconciliationSummary['matched']" valueColor="#16a34a" />
            <x-kpi-card title="Solo CRM" :value="(string) $reconciliationSummary['crm_only']" valueColor="#ca8a04" />
            <x-kpi-card title="Solo Stripe" :value="(string) $reconciliationSummary['stripe_only']" valueColor="#dc2626" />
            <x-kpi-card title="Dispute aperti" :value="(string) $reconciliationSummary['open_disputes']" />
        </div>
        @if ($reconciliationRows->isNotEmpty())
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>Ordine</th>
                            <th>Session</th>
                            <th>Importo</th>
                            <th>Stato</th>
                            <th>Ambiente</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reconciliationRows as $row)
                            <tr wire:key="recon-{{ $row['ordine_id'] ?? $row['stripe_event_id'] }}">
                                <td>{{ $row['ordine_id'] ?? '—' }}</td>
                                <td class="seg-text-muted" style="font-size: 12px;">{{ $row['checkout_session_id'] ?? '—' }}</td>
                                <td>{{ number_format($row['amount_eur'], 2, ',', '.') }} €</td>
                                <td>{{ $row['status'] }}</td>
                                <td>{{ $row['environment'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="seg-text-muted" style="font-size: 13px; margin: 0;">Nessun pagamento Stripe negli ultimi {{ $reconciliationSummary['days'] }} giorni.</p>
        @endif
        <details style="font-size: 13px; margin-top: 12px;">
            <summary class="seg-text-muted" style="cursor: pointer;">Workflow dispute (stub)</summary>
            <ol style="margin: 8px 0 0; padding-left: 20px;">
                @foreach ($disputeWorkflow as $step)
                    <li style="margin-bottom: 4px;"><strong>{{ $step['action'] }}</strong> — {{ $step['detail'] }}</li>
                @endforeach
            </ol>
        </details>
    </div>

    {{-- Dispute tab — hidden in stub mode; shows real disputes when STRIPE_DISPUTE_STUB=false --}}
    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
            <h2 class="mag-section-title" style="margin: 0;">Dispute Stripe</h2>
            @if ($disputeStubEnabled)
                <span class="seg-badge seg-badge-warning" style="font-size: 11px;">STUB attivo</span>
                <span class="seg-text-muted" style="font-size: 12px;">Impostare <code>STRIPE_DISPUTE_STUB=false</code> in produzione per dispute live.</span>
            @else
                <span class="seg-badge" style="background:#dcfce7;color:#166534;font-size:11px;">LIVE</span>
            @endif
        </div>

        @if (! $disputeStubEnabled && $openDisputes->isEmpty())
            <p class="mag-empty">Nessuna dispute aperta. ✅</p>
        @elseif (! $disputeStubEnabled)
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>ID Dispute</th>
                            <th>Ordine</th>
                            <th>Importo</th>
                            <th>Motivo</th>
                            <th>Stato</th>
                            <th>Scadenza prove</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($openDisputes as $d)
                            <tr wire:key="disp-{{ $d->id }}">
                                <td class="seg-text-muted" style="font-size: 12px; font-family: monospace;">{{ $d->stripe_dispute_id }}</td>
                                <td>
                                    @if ($d->ordine)
                                        <a href="{{ route('segreteria.ecommerce.ordini.show', $d->ordine) }}" wire:navigate>#{{ $d->ordine_id }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ number_format($d->amountEur(), 2, ',', '.') }} {{ strtoupper($d->currency) }}</td>
                                <td>{{ $d->reason ?? '—' }}</td>
                                <td>
                                    <span class="seg-badge {{ $d->isOpen() ? 'seg-badge-warning' : '' }}" style="font-size: 11px;">
                                        {{ $d->status }}
                                    </span>
                                </td>
                                <td>
                                    @if ($d->evidence_due_by)
                                        @if ($d->evidenceDueSoon())
                                            <span style="color: #dc2626; font-weight: 600;">{{ $d->evidence_due_by->format('d/m/Y') }}</span>
                                            <span style="font-size: 11px; color: #dc2626;"> ‼ urgente</span>
                                        @else
                                            {{ $d->evidence_due_by->format('d/m/Y') }}
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="seg-text-muted" style="font-size: 13px;">
                Modalità stub attiva — le dispute Stripe vengono solo loggate e non persiste nel DB dedicato.<br>
                Quando pronto per la produzione, impostare <code>STRIPE_DISPUTE_STUB=false</code> e configurare
                <code>STRIPE_DISPUTE_WEBHOOK_SECRET</code>.
            </p>
        @endif
    </div>

    <div class="seg-card seg-card-padding mag-filters">
        <div class="seg-form-grid">
            <x-form-field label="Categoria" name="categoria">
                <select id="categoria" wire:model.live="categoria" class="seg-select">
                    <option value="">Tutte</option>
                    <option value="motore">Motore</option>
                    <option value="carrozzeria">Carrozzeria</option>
                    <option value="elettronica">Elettronica</option>
                    <option value="interni">Interni</option>
                    <option value="generico">Generico</option>
                </select>
            </x-form-field>
            <x-form-field label="Cerca" name="search" hint="Codice prodotto o nome ricambio." class="seg-form-group--span2">
                <input type="search" id="search" wire:model.live.debounce.300ms="search" class="seg-input" placeholder="Codice, nome…" />
            </x-form-field>
        </div>
    </div>

    <div class="seg-card seg-card-padding-none">
        @if ($prodotti->isEmpty())
            <x-empty-state
                :title="$search !== '' || $categoria !== '' ? 'Nessun ricambio trovato' : 'Nessun ricambio in catalogo'"
                :description="$search !== '' || $categoria !== '' ? 'Modifica i filtri di ricerca.' : 'I ricambi compaiono qui dopo la bonifica VFU e l\'inserimento a catalogo.'"
            />
        @else
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Codice</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Prezzo</th>
                        <th>Giacenza</th>
                        <th>VFU</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($prodotti as $p)
                        <tr wire:key="prod-{{ $p->id }}">
                            <td>
                                @if ($url = $immagini->publicUrl($p))
                                    <img src="{{ $url }}" alt="" class="seg-eco-thumb" loading="lazy" />
                                @else
                                    <span class="seg-eco-thumb seg-eco-thumb--empty" aria-hidden="true">—</span>
                                @endif
                            </td>
                            <td class="seg-cell-strong">{{ $p->codice }}</td>
                            <td>{{ $p->nome }}</td>
                            <td>{{ ucfirst($p->categoria) }}</td>
                            <td>{{ $service->prezzoDisplay($p) }}</td>
                            <td>{{ $p->giacenza }}</td>
                            <td>
                                @if ($p->vfuRegistration)
                                    <a href="{{ route('segreteria.vfu.show', $p->vfu_registration_id) }}" wire:navigate>{{ $p->vfuRegistration->targa }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('segreteria.ecommerce.prodotti.show', $p) }}" class="seg-btn seg-btn-secondary seg-btn-sm" wire:navigate>Dettaglio</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @if ($prodotti->hasPages())
            <div class="seg-pagination">{{ $prodotti->links() }}</div>
        @endif
    </div>

    <section class="seg-card seg-card-padding" style="margin-top: 1.5rem;">
        <h2 class="mag-section-title">Ordini recenti</h2>
        <div class="seg-form-group" style="max-width: 240px; margin-bottom: 1rem;">
            <label class="seg-label" for="stato-ordine">Filtra stato</label>
            <select id="stato-ordine" wire:model.live="statoOrdine" class="seg-select">
                <option value="">Tutti</option>
                <option value="bozza">Bozza</option>
                <option value="pagamento_in_attesa">Pagamento in attesa</option>
                <option value="confermato">Confermato</option>
                <option value="annullato">Annullato</option>
            </select>
        </div>
        @if ($ordini->isEmpty())
            <p class="mag-empty">Nessun ordine per i filtri selezionati.</p>
        @else
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Data</th>
                            <th>Cliente</th>
                            <th>Totale</th>
                            <th>Stato</th>
                            <th>Pagamento</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ordini as $ordine)
                            <tr wire:key="ord-list-{{ $ordine->id }}">
                                <td>{{ $ordine->id }}</td>
                                <td>{{ $ordine->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $ordine->user?->name ?? '—' }}</td>
                                <td>{{ number_format((float) $ordine->totale, 2, ',', '.') }} €</td>
                                <td>
                                    <x-badge-stato :stato="$service->statoOrdineBadge($ordine->stato)" :label="$service->statoOrdineLabel($ordine->stato)" />
                                </td>
                                <td>
                                    <x-badge-stato :stato="$service->statoPagamentoBadge($ordine)" :label="$service->statoPagamentoLabel($ordine)" />
                                </td>
                                <td>
                                    <a href="{{ route('segreteria.ecommerce.ordini.show', $ordine) }}" class="seg-btn seg-btn-ghost seg-btn-sm" wire:navigate>Apri</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
