<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header">
        <h1>Audit &amp; activity log</h1>
        <p>Registro centralizzato delle azioni sensibili (RENTRI, e-commerce, MUD, migrazione legacy).
            <a href="{{ route('admin.logs') }}" wire:navigate>Log applicativi strutturati →</a>
        </p>
    </div>

    @if ($businessKpiAlert)
        <div class="seg-card seg-card-padding-sm" style="margin-bottom: 16px;" role="status">
            <strong>KPI business v3</strong>
            <span class="seg-text-muted" style="font-size: 13px;">
                — ultimo check {{ \Illuminate\Support\Carbon::parse($businessKpiAlert['checked_at'])->format('d/m/Y H:i') }}
                · esito {{ strtoupper($businessKpiAlert['overall'] ?? '—') }}
                · <code>kpi:business-check --notify</code>
            </span>
        </div>
    @endif

    <div class="seg-kpi-grid">
        <x-kpi-card title="Eventi registrati" :value="(string) $contatori['totale']" />
    </div>

    <div class="seg-card seg-card-padding mag-filters">
        <div class="seg-form-grid">
            <div class="seg-form-group">
                <label class="seg-label">Modulo</label>
                <select wire:model.live="modulo" class="seg-select">
                    <option value="">Tutti</option>
                    @foreach (App\Domain\Audit\ActivityLogService::MODULI as $m)
                        <option value="{{ $m }}">{{ $service->moduloLabel($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Utente</label>
                <select wire:model.live="user_id" class="seg-select">
                    <option value="">Tutti</option>
                    @foreach ($utenti as $u)
                        <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Data da</label>
                <input type="date" wire:model.live="data_da" class="seg-input" />
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Data a</label>
                <input type="date" wire:model.live="data_a" class="seg-input" />
            </div>
        </div>
    </div>

    @can('viewExports', Spatie\Activitylog\Models\Activity::class)
        <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
            <h2 class="mag-section-title" style="margin-top: 0;">Export live su storage</h2>
            <p class="seg-text-muted" style="font-size: 13px; margin: 0 0 12px;">
                Disk: <code>{{ $exportDisk }}</code> · CLI: <code>php artisan audit:export-scheduled</code>
            </p>
            @if ($exportRuns->isEmpty())
                <p class="seg-text-muted">Nessun export completato. Lo schedule settimanale genera CSV con checksum SHA-256.</p>
            @else
                <div class="seg-table-wrap">
                    <table class="seg-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Periodo</th>
                                <th>Righe</th>
                                <th>SHA-256</th>
                                <th>Stato</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($exportRuns as $run)
                                <tr wire:key="audit-export-{{ $run->id }}">
                                    <td>{{ $run->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $run->period_from?->format('d/m/Y') }} – {{ $run->period_to?->format('d/m/Y') }}</td>
                                    <td>{{ $run->row_count }}</td>
                                    <td><code title="{{ $run->checksum_sha256 }}">{{ Str::limit($run->checksum_sha256, 12, '') }}</code></td>
                                    <td>
                                        @if ($run->status === 'purged' || $run->isExpired())
                                            <span class="seg-badge seg-badge-warning">Scaduto</span>
                                        @else
                                            <span class="seg-badge seg-badge-success">{{ $run->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @can('downloadExport', Spatie\Activitylog\Models\Activity::class)
                                            @if ($run->status === 'completed' && ! $run->isExpired())
                                                <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="downloadExport({{ $run->id }})">
                                                    Download
                                                </button>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endcan

    <div class="seg-card seg-card-padding-none">
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Modulo</th>
                        <th>Descrizione</th>
                        <th>Utente</th>
                        <th>Dettaglio</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activities as $activity)
                        <tr wire:key="act-{{ $activity->id }}">
                            <td>{{ $activity->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $service->moduloLabel((string) $activity->log_name) }}</td>
                            <td>{{ $activity->description }}</td>
                            <td>{{ $activity->causer?->name ?? '—' }}</td>
                            <td>
                                @if ($detail = $service->legacyImportDetail($activity))
                                    <span class="seg-text-muted">{{ $detail }}</span>
                                @elseif (! empty($activity->properties))
                                    <code>{{ Str::limit(json_encode($activity->properties, JSON_UNESCAPED_UNICODE), 60) }}</code>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="seg-table-empty">Nessun evento registrato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($activities->hasPages())
            <div class="seg-pagination">{{ $activities->links() }}</div>
        @endif
    </div>
</div>
