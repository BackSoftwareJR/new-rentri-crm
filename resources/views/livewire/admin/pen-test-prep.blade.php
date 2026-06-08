<div id="pen-test-prep">
    <div class="seg-page-header">
        <h1>Pen-test OWASP esterno — preparazione</h1>
        <p>
            Scope assets, credenziali test e checklist engagement per auditor third-party.
            Documentazione completa in
            <code>docs/PEN-TEST-EXTERNAL-SCOPE.md</code>.
        </p>
    </div>

    <div class="seg-kpi-grid">
        <x-kpi-card
            title="Pronto engagement"
            :value="$summary['ready'] ? 'Sì' : 'No'"
        />
        <x-kpi-card title="Asset in scope" :value="(string) $summary['assets_count']" />
        <x-kpi-card title="Fuori scope" :value="(string) $summary['out_of_scope_count']" />
        <x-kpi-card title="Account test template" :value="(string) $summary['test_accounts_count']" />
        <x-kpi-card
            title="Findings aperti"
            :value="(string) (($remediationSummary['open'] ?? 0) + ($remediationSummary['in_progress'] ?? 0))"
        />
        <x-kpi-card
            title="Gate go-live P0"
            :value="($remediationSummary['go_live_gate_clear'] ?? true) ? 'OK' : 'BLOCCATO'"
        />
    </div>

    @if (session('pen_test_message'))
        <div class="seg-alert seg-alert-success" style="margin-bottom: 16px;">
            {{ session('pen_test_message') }}
        </div>
    @endif

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h2 class="mag-section-title" style="margin: 0;">Remediation findings</h2>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" wire:click="showAddFindingForm" class="seg-btn seg-btn-primary">
                    + Aggiungi finding
                </button>
                <button type="button" wire:click="exportRemediation" class="seg-btn seg-btn-ghost">
                    Esporta template remediation
                </button>
            </div>
        </div>
        <p class="seg-text-muted" style="font-size: 13px; margin: 8px 0 16px;">
            Registro vendor findings — workflow in
            <code>docs/REMEDIATION-FINDINGS-TEMPLATE.md</code>.
            P0 aperti: <strong>{{ $remediationSummary['open_by_severity']['P0'] ?? 0 }}</strong> ·
            P1 aperti: <strong>{{ $remediationSummary['open_by_severity']['P1'] ?? 0 }}</strong> ·
            Chiusi: <strong>{{ $remediationSummary['closed'] ?? 0 }}</strong>
        </p>

        @if ($showAddForm)
            <form wire:submit="addFinding" style="margin-bottom: 16px; padding: 16px; border: 1px solid var(--seg-border, #e5e7eb); border-radius: 8px;">
                <div style="display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                    <div style="grid-column: 1 / -1;">
                        <label class="seg-label">Titolo *</label>
                        <input type="text" wire:model="formTitle" class="seg-input" placeholder="Es. IDOR su export audit">
                        @error('formTitle') <span class="seg-error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="seg-label">Severità *</label>
                        <select wire:model="formSeverity" class="seg-input">
                            @foreach ($severities as $severity)
                                <option value="{{ $severity->value }}">{{ $severity->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="seg-label">Owner</label>
                        <input type="text" wire:model="formOwner" class="seg-input" placeholder="Team / persona">
                    </div>
                    <div>
                        <label class="seg-label">Asset in scope</label>
                        <select wire:model="formAssetKey" class="seg-input">
                            <option value="">— Nessuno —</option>
                            @foreach ($assets as $asset)
                                <option value="{{ $asset['key'] }}">{{ $asset['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="seg-label">Sprint ref</label>
                        <input type="text" wire:model="formSprintRef" class="seg-input" placeholder="113">
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="seg-label">Evidenza / note</label>
                        <textarea wire:model="formEvidence" class="seg-input" rows="3" placeholder="PoC, link PR, note vendor"></textarea>
                    </div>
                </div>
                <div style="margin-top: 12px; display: flex; gap: 8px;">
                    <button type="submit" class="seg-btn seg-btn-primary">Salva finding</button>
                    <button type="button" wire:click="cancelAddFinding" class="seg-btn seg-btn-ghost">Annulla</button>
                </div>
            </form>
        @endif

        @if ($findings === [])
            <p class="seg-text-muted" style="margin: 0;">Nessun finding registrato. Aggiungere dopo report vendor.</p>
        @else
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Severità</th>
                            <th>Titolo</th>
                            <th>Asset</th>
                            <th>Stato</th>
                            <th>Owner</th>
                            <th>Sprint</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($findings as $finding)
                            <tr>
                                <td><code>{{ $finding['id'] }}</code></td>
                                <td>
                                    <span @class([
                                        'seg-badge',
                                        'seg-badge-danger' => $finding['severity'] === 'P0',
                                        'seg-badge-warning' => in_array($finding['severity'], ['P1', 'P2'], true),
                                        'seg-badge-muted' => $finding['severity'] === 'P3',
                                    ])>{{ $finding['severity'] }}</span>
                                </td>
                                <td>{{ $finding['title'] }}</td>
                                <td>{{ $finding['asset_key'] !== '' ? $finding['asset_key'] : '—' }}</td>
                                <td>{{ $finding['status'] }}</td>
                                <td>{{ $finding['owner'] !== '' ? $finding['owner'] : '—' }}</td>
                                <td>{{ $finding['sprint_ref'] !== '' ? $finding['sprint_ref'] : '—' }}</td>
                                <td>
                                    @if ($finding['status'] !== 'closed')
                                        @if ($closingId === $finding['id'])
                                            <div style="min-width: 220px;">
                                                <textarea wire:model="closeEvidence" class="seg-input" rows="2" placeholder="Evidenza chiusura"></textarea>
                                                <div style="margin-top: 6px; display: flex; gap: 4px;">
                                                    <button type="button" wire:click="closeFinding" class="seg-btn seg-btn-primary seg-btn-sm">Conferma</button>
                                                    <button type="button" wire:click="cancelCloseFinding" class="seg-btn seg-btn-ghost seg-btn-sm">Annulla</button>
                                                </div>
                                            </div>
                                        @else
                                            <button type="button" wire:click="markInProgress('{{ $finding['id'] }}')" class="seg-btn seg-btn-ghost seg-btn-sm">In corso</button>
                                            <button type="button" wire:click="startCloseFinding('{{ $finding['id'] }}')" class="seg-btn seg-btn-primary seg-btn-sm">Chiudi</button>
                                        @endif
                                    @else
                                        <span class="seg-text-muted" style="font-size: 12px;">Chiuso</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Scope × findings aperti</h2>
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Asset</th>
                        <th>Path</th>
                        <th>Findings aperti</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($scopeRemediation as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td><code>{{ $row['path'] }}</code></td>
                            <td>
                                @if ($row['open_findings'] > 0)
                                    <span class="seg-badge seg-badge-warning">{{ $row['open_findings'] }}</span>
                                @else
                                    <span class="seg-text-muted">0</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Checklist engagement</h2>
        <ul class="seg-list-check" style="list-style: none; padding: 0; margin: 0;">
            @foreach ($checklist as $item)
                <li style="margin-bottom: 8px;">
                    @if ($item['ok'])
                        <span aria-hidden="true">✅</span>
                    @else
                        <span aria-hidden="true">⬜</span>
                    @endif
                    {{ $item['label'] }}
                    @if ($item['hint'])
                        <span class="seg-text-muted" style="font-size: 13px;"> — {{ $item['hint'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="seg-text-muted" style="font-size: 13px; margin: 12px 0 0;">
            2FA enforced: {{ $summary['two_factor_enforced'] ? 'sì' : 'no' }} ·
            Stripe produzione: {{ $summary['stripe_production'] ? 'sì (attenzione)' : 'no (sandbox/stub)' }}
        </p>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Asset in scope</h2>
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Superficie</th>
                        <th>Path</th>
                        <th>Metodo</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($assets as $asset)
                        <tr>
                            <td>{{ $asset['label'] }}</td>
                            <td><code>{{ $asset['path'] }}</code></td>
                            <td>{{ $asset['method'] }}</td>
                            <td class="seg-text-muted" style="font-size: 13px;">{{ $asset['notes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Fuori scope</h2>
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($outOfScope as $item)
                <li style="margin-bottom: 6px;">
                    <strong>{{ $item['label'] }}</strong>
                    <span class="seg-text-muted"> — {{ $item['reason'] }}</span>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <h2 class="mag-section-title" style="margin-top: 0;">Template account test (placeholder)</h2>
        <p class="seg-text-muted" style="font-size: 13px;">
            Creare utenti dedicati su staging prima dell'engagement. Non usare credenziali produzione.
        </p>
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Ruolo</th>
                        <th>Email</th>
                        <th>2FA</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($testAccounts as $account)
                        <tr>
                            <td>{{ $account['role'] }}</td>
                            <td><code>{{ $account['email'] }}</code></td>
                            <td>{{ $account['two_factor'] }}</td>
                            <td class="seg-text-muted" style="font-size: 13px;">{{ $account['notes'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="seg-card seg-card-padding">
        <h2 class="mag-section-title" style="margin-top: 0;">Documentazione</h2>
        <ul style="margin: 0; padding-left: 20px;">
            @foreach ($documents as $key => $path)
                <li><code>{{ $path }}</code></li>
            @endforeach
        </ul>
        <p style="margin: 12px 0 0;">
            <a href="{{ route('admin.waf-status') }}" class="seg-btn seg-btn-ghost">WAF deploy status →</a>
        </p>
    </div>
</div>
