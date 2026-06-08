<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <p class="mag-back">
                <a href="{{ route('segreteria.fir') }}" wire:navigate>← Formulari FIR</a>
            </p>
            <h1>Blocchi FIR</h1>
            <p>Gestione blocchi progressivi per vidimazione RENTRI (sync API o creazione manuale).</p>
        </div>
        <div class="seg-page-header-actions">
            <button type="button" class="seg-btn seg-btn-secondary" wire:click="syncDaRentri" wire:loading.attr="disabled" wire:confirm="Importare i blocchi FIR disponibili da RENTRI?">
                <span wire:loading.remove wire:target="syncDaRentri">Sincronizza da RENTRI</span>
                <span wire:loading wire:target="syncDaRentri">Sync in corso…</span>
            </button>
        </div>
    </div>

    <div class="mag-detail-grid">
        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Nuovo blocco</h2>
            <p class="mag-section-lead">Ogni blocco associa un codice al numero iscrizione sito RENTRI.</p>
            <form wire:submit="salvaBlocco" class="seg-form-grid">
                <div class="seg-form-group">
                    <label class="seg-label">Codice blocco *</label>
                    <input type="text" wire:model="codice_blocco" class="seg-input @error('codice_blocco') is-invalid @enderror" placeholder="Es. BLK-2026-01" />
                    @error('codice_blocco') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group">
                    <label class="seg-label">N. iscrizione sito *</label>
                    <input type="text" wire:model="num_iscr_sito" class="seg-input @error('num_iscr_sito') is-invalid @enderror" />
                    @error('num_iscr_sito') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-form-group seg-form-group--span2">
                    <button type="submit" class="seg-btn seg-btn-primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="salvaBlocco">Crea blocco</span>
                        <span wire:loading wire:target="salvaBlocco">Salvataggio…</span>
                    </button>
                </div>
            </form>
        </section>

        <section class="seg-card seg-card-padding-none">
            <div class="seg-page-header seg-page-header--compact" style="padding: 1rem 1.25rem;">
                <h2 class="mag-section-title" style="margin: 0;">Blocchi configurati</h2>
            </div>
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>Codice blocco</th>
                            <th>N. iscrizione sito</th>
                            <th>Ultimo progressivo</th>
                            <th>Disponibilità</th>
                            <th>FIR emessi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($blocchi as $b)
                            <tr wire:key="blk-{{ $b->id }}">
                                <td class="seg-cell-strong">{{ $b->codice_blocco }}</td>
                                <td>{{ $b->num_iscr_sito }}</td>
                                <td>{{ $b->progressivo_ultimo }}</td>
                                <td>
                                    @if ($service->isEsaurito($b))
                                        <x-badge-stato stato="danger" label="Esaurito" />
                                    @else
                                        <x-badge-stato stato="success" label="{{ $service->conteggioDisponibile($b) }} disp." />
                                    @endif
                                </td>
                                <td>{{ $b->firs_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="seg-table-empty">Nessun blocco configurato. Creane uno per vidimare FIR.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
