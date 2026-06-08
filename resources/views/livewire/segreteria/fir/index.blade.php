<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <h1>Formulari FIR</h1>
            <p>Elenco formulari vidimati RENTRI collegati ai trasporti.</p>
        </div>
        <div class="seg-header-actions">
            <x-rentri-api-mode-badge />
            @if ($contatori['tracking_stub'] > 0)
                <x-badge-stato stato="info" :label="'Tracking stub: ' . $contatori['tracking_stub']" />
            @endif
            <button type="button" wire:click="exportBulkCsv" class="seg-btn seg-btn-secondary">
                Export CSV bulk
            </button>
            <a href="{{ route('segreteria.fir.blocchi') }}" class="seg-btn seg-btn-secondary" wire:navigate>Gestione blocchi</a>
        </div>
    </div>

    <div class="seg-kpi-grid">
        <x-kpi-card title="Totale FIR" :value="(string) $contatori['totale']" />
        <x-kpi-card title="Vidimati" :value="(string) $contatori['vidimati']" valueColor="#16a34a" />
        <x-kpi-card title="Firmati xFIR" :value="(string) $contatori['firmati']" valueColor="#2563eb" />
    </div>

    <div class="seg-card seg-card-padding mag-filters">
        <div class="seg-form-grid">
            <div class="seg-form-group">
                <label class="seg-label">Stato</label>
                <select wire:model.live="stato" class="seg-select">
                    <option value="">Tutti</option>
                    <option value="vidimato">Vidimato</option>
                    <option value="firmato">Firmato xFIR</option>
                    <option value="bozza">Bozza</option>
                    <option value="trasmesso">Trasmesso</option>
                    <option value="annullato">Annullato</option>
                </select>
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Vidimato dal</label>
                <input type="date" wire:model.live="data_da" class="seg-input" />
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Vidimato al</label>
                <input type="date" wire:model.live="data_a" class="seg-input" />
            </div>
            <div class="seg-form-group seg-form-group--span2">
                <label class="seg-label">Cerca</label>
                <input type="search" wire:model.live.debounce.300ms="search" class="seg-input" placeholder="Numero FIR, blocco, codice CER…" />
            </div>
        </div>
    </div>

    <div class="seg-card seg-card-padding-none">
        @if ($firs->isEmpty())
            <x-empty-state
                :title="$search !== '' || $stato !== '' || $data_da !== '' || $data_a !== '' ? 'Nessun FIR trovato' : 'Nessun FIR registrato'"
                :description="$search !== '' || $stato !== '' || $data_da !== '' || $data_a !== '' ? 'Prova a modificare i filtri.' : 'Vidima un FIR dal dettaglio di un trasporto in transito.'"
                action-label="Vai ai trasporti"
                :action-href="route('segreteria.trasporti')"
            />
        @else
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Numero FIR</th>
                        <th>Blocco</th>
                        <th>Progressivo</th>
                        <th>Stato</th>
                        <th>Vidimato il</th>
                        <th>Trasporto</th>
                        <th>Tracking</th>
                        <th>CER</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($firs as $f)
                        <tr wire:key="fir-{{ $f->id }}">
                            <td class="seg-cell-strong">{{ $firService->numeroDisplay($f) }}</td>
                            <td>{{ $f->codice_blocco }}</td>
                            <td>{{ $f->progressivo }}</td>
                            <td>
                                <x-badge-stato :stato="$firService->statoBadgeVariant($f->stato->value)" :label="$firService->statoLabel($f->stato->value)" />
                            </td>
                            <td>{{ $f->vidimato_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>
                                @if ($f->trasporto_id)
                                    <a href="{{ route('segreteria.trasporti.show', $f->trasporto_id) }}" wire:navigate>#{{ $f->trasporto_id }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if ($firService->hasTrackingStub($f))
                                    <x-badge-stato stato="info" label="GPS stub" />
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $f->trasporto?->codiceCer?->codice ?? '—' }}</td>
                            <td>
                                @if ($f->vidimato_at)
                                    <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm"
                                        wire:click="downloadFirPdf({{ $f->id }})">
                                        Scarica FIR PDF
                                    </button>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        @if ($firs->hasPages())
            <div class="seg-pagination">{{ $firs->links() }}</div>
        @endif
    </div>
</div>
