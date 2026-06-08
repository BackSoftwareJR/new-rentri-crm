<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header">
        <h1>Log applicativi</h1>
        <p>Observability strutturata per produzione — correlazione trace_id, moduli RENTRI/e-commerce/GPS/Stripe/sicurezza.</p>
    </div>

    <div class="seg-kpi-grid">
        <x-kpi-card title="Eventi registrati" :value="(string) $contatori['totale']" />
    </div>

    <div class="seg-card seg-card-padding mag-filters">
        <div class="seg-form-grid">
            <div class="seg-form-group">
                <label class="seg-label">Modulo</label>
                <select wire:model.live="module" class="seg-select">
                    <option value="">Tutti</option>
                    @foreach ($modules as $m)
                        <option value="{{ $m }}">{{ $service->moduloLabel($m) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Livello</label>
                <select wire:model.live="level" class="seg-select">
                    <option value="">Tutti</option>
                    @foreach ($levels as $l)
                        <option value="{{ $l }}">{{ strtoupper($l) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Trace ID</label>
                <input type="text" wire:model.live.debounce.400ms="trace_id" class="seg-input" placeholder="UUID / X-Request-Id" />
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Demo</label>
                <select wire:model.live="demo" class="seg-select">
                    <option value="">Tutti</option>
                    <option value="1">Solo demo</option>
                    <option value="0">Solo produzione</option>
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
        <div style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
            @can('export', App\Models\ApplicationLog::class)
                <a href="{{ $exportUrl }}" class="seg-btn seg-btn-secondary">Export CSV</a>
            @endcan
            <a href="{{ route('admin.audit') }}" class="seg-btn seg-btn-ghost" wire:navigate>Audit activity log →</a>
        </div>
        <p class="seg-text-muted" style="font-size: 13px; margin: 12px 0 0;">
            CLI: <code>php artisan logs:health</code> · <code>php artisan logs:purge --days={{ config('application_log.retention_days') }}</code>
        </p>
    </div>

    @if ($selected)
        <div class="seg-card seg-card-padding" style="margin-bottom: 16px;" role="region" aria-label="Dettaglio log">
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                <h2 class="mag-section-title" style="margin: 0;">Dettaglio #{{ $selected->id }}</h2>
                <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm" wire:click="closeDetail">Chiudi</button>
            </div>
            <dl class="seg-dl" style="margin-top: 12px;">
                <dt>Trace ID</dt><dd><code>{{ $selected->trace_id }}</code></dd>
                <dt>Livello</dt><dd>{{ strtoupper($selected->level) }}</dd>
                <dt>Modulo</dt><dd>{{ $service->moduloLabel($selected->module) }}</dd>
                <dt>Azione</dt><dd><code>{{ $selected->action }}</code></dd>
                <dt>Messaggio</dt><dd>{{ $selected->message }}</dd>
                <dt>Esito</dt><dd>{{ $selected->outcome ?? '—' }}</dd>
                <dt>Durata</dt><dd>{{ $selected->duration_ms !== null ? $selected->duration_ms.' ms' : '—' }}</dd>
                <dt>Utente</dt><dd>{{ $selected->user?->name ?? '—' }}</dd>
                <dt>Demo</dt><dd>{{ $selected->demo_mode ? 'sì' : 'no' }}</dd>
                <dt>Data</dt><dd>{{ $selected->created_at?->format('d/m/Y H:i:s') }}</dd>
            </dl>
            @if (! empty($selected->context))
                <pre class="seg-code-block" style="margin-top: 12px; max-height: 240px; overflow: auto;">{{ json_encode($selected->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            @endif
        </div>
    @endif

    <div class="seg-card seg-card-padding-none">
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Livello</th>
                        <th>Modulo</th>
                        <th>Azione</th>
                        <th>Messaggio</th>
                        <th>Trace</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        <tr wire:key="log-{{ $entry->id }}">
                            <td>{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <span @class([
                                    'seg-badge',
                                    'seg-badge-danger' => in_array($entry->level, ['error', 'critical', 'alert', 'emergency'], true),
                                    'seg-badge-warning' => $entry->level === 'warning',
                                    'seg-badge-success' => in_array($entry->level, ['info', 'notice', 'debug'], true),
                                ])>{{ strtoupper($entry->level) }}</span>
                            </td>
                            <td>{{ $service->moduloLabel($entry->module) }}</td>
                            <td><code>{{ Str::limit($entry->action, 24) }}</code></td>
                            <td>{{ Str::limit($entry->message, 64) }}</td>
                            <td><code title="{{ $entry->trace_id }}">{{ Str::limit($entry->trace_id, 10) }}</code></td>
                            <td>
                                <button type="button" class="seg-btn seg-btn-ghost seg-btn-sm" wire:click="showDetail({{ $entry->id }})">
                                    Dettaglio
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="seg-table-empty">Nessun log registrato.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($entries->hasPages())
            <div class="seg-pagination">{{ $entries->links() }}</div>
        @endif
    </div>
</div>
