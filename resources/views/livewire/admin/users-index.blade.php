<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header">
        <h1>Gestione utenti</h1>
        <p>Crea, modifica e gestisci gli accessi al CRM. Ogni azione è registrata nel log di sicurezza.</p>
    </div>

    {{-- Toolbar: search + role filter + create button --}}
    <div class="seg-card seg-card-padding mag-filters" style="margin-bottom: 16px;">
        <div class="seg-form-grid" style="align-items: flex-end;">
            <div class="seg-form-group">
                <label class="seg-label">Cerca</label>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Nome o email…"
                    class="seg-input"
                    autocomplete="off"
                />
            </div>
            <div class="seg-form-group">
                <label class="seg-label">Ruolo</label>
                <select wire:model.live="role" class="seg-select">
                    <option value="">Tutti i ruoli</option>
                    @foreach ($rolesAvail as $r)
                        <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="seg-form-group" style="flex: 0 0 auto;">
                @can('create', App\Models\User::class)
                    <button
                        type="button"
                        wire:click="openCreateModal"
                        class="seg-btn seg-btn-primary"
                        style="white-space: nowrap;"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true" style="margin-right: 6px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Nuovo utente
                    </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Users table --}}
    <div class="seg-card seg-card-padding-none">
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Ruolo</th>
                        <th>2FA</th>
                        <th>Ultimo accesso</th>
                        <th>Creato</th>
                        <th>Stato</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr wire:key="user-{{ $u->id }}">
                            <td>
                                <span style="font-weight: 500;">{{ $u->name }}</span>
                                @if ($u->id === auth()->id())
                                    <span class="seg-badge" style="margin-left: 6px; font-size: 10px; background: var(--seg-surface-2); color: var(--seg-text-2);">Tu</span>
                                @endif
                            </td>
                            <td class="seg-text-muted">{{ $u->email }}</td>
                            <td>
                                @php $roleName = $u->getRoleNames()->first() ?? null; @endphp
                                @if ($roleName === 'admin')
                                    <span class="seg-badge seg-badge-danger" style="text-transform: uppercase; font-size: 10px; letter-spacing: .04em;">Admin</span>
                                @elseif ($roleName === 'segreteria')
                                    <span class="seg-badge seg-badge-primary" style="text-transform: capitalize;">Segreteria</span>
                                @elseif ($roleName === 'operatore')
                                    <span class="seg-badge seg-badge-info" style="text-transform: capitalize;">Operatore</span>
                                @elseif ($roleName === 'editor')
                                    <span class="seg-badge seg-badge-warning" style="text-transform: capitalize;">Editor</span>
                                @else
                                    <span class="seg-badge" style="background: var(--seg-surface-2); color: var(--seg-text-3);">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($u->hasTwoFactorEnabled())
                                    <span class="seg-badge seg-badge-success" title="2FA attivo">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="11" height="11" aria-hidden="true" style="margin-right: 3px;"><polyline points="20 6 9 17 4 12"/></svg>
                                        Attivo
                                    </span>
                                @else
                                    <span class="seg-badge" style="background: var(--seg-surface-2); color: var(--seg-text-3);">Off</span>
                                @endif
                            </td>
                            <td class="seg-text-muted" style="font-size: 13px;">
                                {{ $u->last_login_at ? $u->last_login_at->format('d/m/Y H:i') : '—' }}
                            </td>
                            <td class="seg-text-muted" style="font-size: 13px;">
                                {{ $u->created_at->format('d/m/Y') }}
                            </td>
                            <td>
                                @if ($u->active)
                                    <span class="seg-badge seg-badge-success">Attivo</span>
                                @else
                                    <span class="seg-badge seg-badge-warning">Inattivo</span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px; justify-content: flex-end; flex-wrap: nowrap;">
                                    @can('update', $u)
                                        <button
                                            type="button"
                                            wire:click="openEditModal({{ $u->id }})"
                                            class="seg-btn seg-btn-secondary seg-btn-sm"
                                            title="Modifica utente"
                                        >
                                            Modifica
                                        </button>
                                    @endcan

                                    @can('toggleActive', $u)
                                        <button
                                            type="button"
                                            wire:click="toggleActive({{ $u->id }})"
                                            wire:confirm="{{ $u->active ? 'Disattivare questo utente?' : 'Riattivare questo utente?' }}"
                                            class="seg-btn seg-btn-secondary seg-btn-sm"
                                            title="{{ $u->active ? 'Disattiva' : 'Riattiva' }}"
                                        >
                                            {{ $u->active ? 'Disattiva' : 'Attiva' }}
                                        </button>
                                    @endcan

                                    @can('resetPassword', $u)
                                        <button
                                            type="button"
                                            wire:click="sendPasswordReset({{ $u->id }})"
                                            wire:confirm="Inviare email di reset password?"
                                            class="seg-btn seg-btn-secondary seg-btn-sm"
                                            title="Reset password"
                                        >
                                            Reset pwd
                                        </button>
                                    @endcan

                                    @can('forceDisable2fa', $u)
                                        @if ($u->hasTwoFactorEnabled())
                                            <button
                                                type="button"
                                                wire:click="forceDisable2fa({{ $u->id }})"
                                                wire:confirm="Disabilitare forzatamente il 2FA di questo utente? L'azione è registrata nel log."
                                                class="seg-btn seg-btn-secondary seg-btn-sm"
                                                title="Forza disabilitazione 2FA"
                                            >
                                                Disab. 2FA
                                            </button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="seg-table-empty">
                                @if ($search !== '' || $role !== '')
                                    Nessun utente trovato per i filtri applicati.
                                @else
                                    Nessun utente registrato.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="seg-pagination">{{ $users->links() }}</div>
        @endif
    </div>

    {{-- Create / Edit modal --}}
    @if ($showModal)
        <div
            class="seg-modal-overlay"
            role="dialog"
            aria-modal="true"
            aria-labelledby="user-modal-title"
            wire:keydown.escape="closeModal"
        >
            <div class="seg-modal" style="max-width: 480px; width: 100%;">
                <div class="seg-modal-header">
                    <h2 id="user-modal-title" class="seg-modal-title">
                        {{ $isEditing ? 'Modifica utente' : 'Nuovo utente' }}
                    </h2>
                    <button type="button" wire:click="closeModal" class="seg-modal-close" aria-label="Chiudi">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div class="seg-modal-body">
                    <form wire:submit="save" id="user-form" novalidate>
                        <div class="seg-form-group" style="margin-bottom: 16px;">
                            <label class="seg-label" for="form-name">Nome</label>
                            <input
                                id="form-name"
                                type="text"
                                wire:model="formName"
                                class="seg-input @error('formName') seg-input-error @enderror"
                                autocomplete="off"
                                placeholder="Mario Rossi"
                            />
                            @error('formName')
                                <span class="seg-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="seg-form-group" style="margin-bottom: 16px;">
                            <label class="seg-label" for="form-email">Email</label>
                            <input
                                id="form-email"
                                type="email"
                                wire:model="formEmail"
                                class="seg-input @error('formEmail') seg-input-error @enderror"
                                autocomplete="off"
                                placeholder="mario@azienda.it"
                            />
                            @error('formEmail')
                                <span class="seg-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="seg-form-group" style="margin-bottom: 16px;">
                            <label class="seg-label" for="form-role">Ruolo</label>
                            <select
                                id="form-role"
                                wire:model="formRole"
                                class="seg-select @error('formRole') seg-input-error @enderror"
                                @if ($isEditing && $editingUserId === auth()->id()) disabled @endif
                            >
                                <option value="">Seleziona ruolo…</option>
                                @foreach ($rolesAvail as $r)
                                    <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                                @endforeach
                            </select>
                            @if ($isEditing && $editingUserId === auth()->id())
                                <span class="seg-text-muted" style="font-size: 12px; margin-top: 4px; display: block;">Non puoi modificare il tuo stesso ruolo.</span>
                            @endif
                            @error('formRole')
                                <span class="seg-field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        @if (! $isEditing)
                            <div class="seg-form-group" style="margin-bottom: 16px;">
                                <label class="seg-label" for="form-password">
                                    Password temporanea
                                    <span class="seg-text-muted" style="font-weight: 400; font-size: 12px;">(auto-generata)</span>
                                </label>
                                <x-password-strength-indicator>
                                    <input
                                        id="form-password"
                                        type="text"
                                        wire:model="formPassword"
                                        x-model="pwd"
                                        class="seg-input seg-input-mono @error('formPassword') seg-input-error @enderror"
                                        autocomplete="new-password"
                                    />
                                </x-password-strength-indicator>
                                @error('formPassword')
                                    <span class="seg-field-error">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="seg-form-group" style="margin-bottom: 8px;">
                                <label class="seg-checkbox-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input
                                        type="checkbox"
                                        wire:model="formSendWelcome"
                                        class="seg-checkbox"
                                    />
                                    <span>Invia email di benvenuto con link reset password</span>
                                </label>
                            </div>
                        @endif
                    </form>
                </div>

                <div class="seg-modal-footer">
                    <button type="button" wire:click="closeModal" class="seg-btn seg-btn-ghost">
                        Annulla
                    </button>
                    <button
                        type="submit"
                        form="user-form"
                        class="seg-btn seg-btn-primary"
                        wire:loading.attr="disabled"
                        wire:target="save"
                    >
                        <span wire:loading.remove wire:target="save">
                            {{ $isEditing ? 'Salva modifiche' : 'Crea utente' }}
                        </span>
                        <span wire:loading wire:target="save">Salvataggio…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
