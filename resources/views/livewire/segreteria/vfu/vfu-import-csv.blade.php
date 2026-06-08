<div>
    <button type="button" class="seg-btn seg-btn-secondary" wire:click="openModal">
        Importa da CSV
    </button>

    @if ($showModal)
        <div class="seg-modal-overlay" wire:keydown.escape="closeModal">
            <div class="seg-modal" role="dialog" aria-modal="true" style="max-width:720px;width:100%;">
                <div class="seg-modal-header">
                    <h2>Importa VFU da CSV</h2>
                    <button type="button" class="seg-modal-close" wire:click="closeModal" aria-label="Chiudi">&times;</button>
                </div>
                <div class="seg-modal-body">
                    <p class="seg-text-muted" style="font-size:13px;margin-top:0;">
                        Scarica il template, compila una riga per veicolo e carica il file. Verranno create pratiche in bozza / accettazione.
                    </p>

                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
                        <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm" wire:click="downloadTemplate">
                            Scarica template CSV
                        </button>
                    </div>

                    <div class="seg-form-group">
                        <label class="seg-label">File CSV</label>
                        <input type="file" wire:model="csvFile" accept=".csv,text/csv" class="seg-input" />
                        @error('csvFile') <p class="seg-field-error">{{ $message }}</p> @enderror
                        <div wire:loading wire:target="csvFile" class="seg-text-muted" style="font-size:12px;margin-top:4px;">Analisi file…</div>
                    </div>

                    @if ($previewRows !== [])
                        <h3 style="font-size:14px;margin:16px 0 8px;">Anteprima (prime 5 righe)</h3>
                        <div class="seg-table-wrap">
                            <table class="seg-table">
                                <thead>
                                    <tr>
                                        @foreach (\App\Domain\Vfu\VfuAccettazioneService::csvImportHeaders() as $header)
                                            <th>{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($previewRows as $row)
                                        <tr wire:key="preview-{{ $loop->index }}">
                                            @foreach (\App\Domain\Vfu\VfuAccettazioneService::csvImportHeaders() as $header)
                                                <td>{{ $row[$header] ?? '' }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($importResult !== null)
                        <div class="seg-card seg-card-padding-sm" style="margin-top:16px;background:#f8fafc;">
                            <strong>{{ $importResult['imported'] }} importati, {{ count($importResult['errors']) }} errori</strong>
                            @if ($importResult['errors'] !== [])
                                <ul style="margin:8px 0 0;padding-left:18px;font-size:13px;">
                                    @foreach ($importResult['errors'] as $error)
                                        <li wire:key="err-{{ $error['row'] }}">
                                            Riga {{ $error['row'] }}: {{ $error['message'] }}
                                            @if (! empty($error['data']['targa']))
                                                ({{ $error['data']['targa'] }})
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="seg-modal-footer">
                    <button type="button" class="seg-btn seg-btn-secondary" wire:click="closeModal">Chiudi</button>
                    <button type="button" class="seg-btn seg-btn-primary" wire:click="import" wire:loading.attr="disabled" @disabled($csvFile === null)>
                        <span wire:loading.remove wire:target="import">Importa</span>
                        <span wire:loading wire:target="import">Importazione…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
