@props([
    'breadcrumb' => 'Home',
    'current' => null,
    'role' => 'Segreteria',
    'user' => 'Utente',
])

<header class="seg-topbar">
    <nav class="seg-breadcrumb" aria-label="Breadcrumb">
        <span>{{ $breadcrumb }}</span>
        @if ($current)
            <span> / </span>
            <span class="seg-breadcrumb-current">{{ $current }}</span>
        @endif
    </nav>

    <div class="seg-topbar-search seg-global-search" id="seg-global-search-root">
        <div class="seg-global-search-field">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input
                type="search"
                id="seg-global-search-input"
                autocomplete="off"
                placeholder="Cerca: VFU, CER, anagrafiche, pagine…"
                aria-label="Ricerca globale"
                aria-describedby="seg-global-search-hint"
                title="Ricerca globale — funzione in arrivo"
                disabled
            />
        </div>
        <p id="seg-global-search-hint" class="seg-sr-only">Ricerca globale non ancora disponibile.</p>
    </div>

    <div class="seg-topbar-actions">
        <button
            type="button"
            id="seg-tablet-nav-toggle"
            class="seg-icon-btn seg-tablet-nav-toggle"
            hidden
            aria-expanded="false"
            aria-label="Apri menu laterale"
            title="Menu"
        >
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="20" y2="18"/></svg>
        </button>

        <button
            type="button"
            id="seg-contrast-toggle"
            class="seg-icon-btn"
            data-seg-contrast-toggle
            aria-pressed="false"
            aria-label="Attiva alto contrasto"
            title="Alto contrasto"
        >
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 2a10 10 0 0 1 0 20V2Z"/></svg>
        </button>

        <button
            type="button"
            id="seg-theme-toggle"
            class="seg-icon-btn"
            data-seg-theme-toggle
            aria-pressed="false"
            aria-label="Attiva tema scuro"
            title="Tema scuro"
        >
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
        </button>

        @if (($horizonMonitorAvailable ?? false) && ($horizonMonitorCanAccess ?? false))
            <a href="{{ $horizonMonitorUrl ?? '/horizon' }}" class="seg-btn seg-btn-secondary seg-btn-sm seg-horizon-link" target="_blank" rel="noopener noreferrer">
                Horizon
            </a>
        @elseif ($horizonMonitorAvailable ?? false)
            <span class="seg-badge seg-badge-warning seg-horizon-stub" title="Horizon installato — accesso riservato admin">Queue</span>
        @endif

        <div class="seg-role-dropdown">
            <button type="button" class="seg-role-btn" aria-haspopup="listbox" aria-label="Ruolo">
                <span>{{ $role }}</span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            </button>
        </div>

        <button type="button" class="seg-icon-btn" aria-label="Notifiche" title="Notifiche">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            <span class="seg-noti-dot" hidden></span>
        </button>

        <button type="button" class="seg-user-btn" aria-label="Utente">
            <span class="seg-user-avatar">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <span>{{ $user }}</span>
        </button>
    </div>
</header>
