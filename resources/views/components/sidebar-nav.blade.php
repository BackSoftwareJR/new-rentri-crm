@props(['active' => null])

@php
    $groups = [
        [
            'label' => 'Operativo',
            'items' => [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'segreteria.dashboard', 'path' => '/segreteria', 'tip' => 'Panoramica KPI', 'icon' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>'],
                ['key' => 'anagrafiche', 'label' => 'Anagrafiche', 'route' => 'segreteria.anagrafiche', 'path' => '/segreteria/anagrafiche', 'tip' => 'Impianti e trasportatori', 'icon' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
                ['key' => 'vfu', 'label' => 'Veicoli (VFU)', 'route' => 'segreteria.vfu.index', 'path' => '/segreteria/vfu', 'tip' => 'Pratiche demolizione', 'icon' => '<path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9-.6 1.3-.6 2.7-.6 2.7s-.6 1.5.5 2.2c.4.3.9.5 1.4.5h2"/><circle cx="7" cy="17" r="2"/><path d="M9 17h6"/><circle cx="17" cy="17" r="2"/>'],
                ['key' => 'codici-cer', 'label' => 'Codici CER', 'route' => 'segreteria.codici-cer.index', 'path' => '/segreteria/codici-cer', 'tip' => 'Catalogo rifiuti', 'icon' => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h10"/><circle cx="17" cy="17" r="3"/>'],
                ['key' => 'magazzino', 'label' => 'Magazzino Rifiuti', 'route' => 'segreteria.magazzino', 'path' => '/segreteria/magazzino', 'tip' => 'Giacenze per CER', 'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>'],
                ['key' => 'registro-movimenti', 'label' => 'Registro movimenti', 'route' => 'segreteria.registro-movimenti', 'path' => '/segreteria/registro-movimenti', 'tip' => 'Carichi e scarichi', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/>'],
                ['key' => 'trasporti', 'label' => 'Trasporti rifiuti', 'route' => 'segreteria.trasporti', 'path' => '/segreteria/trasporti', 'tip' => 'Spedizioni e FIR', 'icon' => '<rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-3"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
            ],
        ],
        [
            'label' => 'RENTRI',
            'items' => [
                ['key' => 'fir', 'label' => 'Formulari (FIR)', 'route' => 'segreteria.fir', 'path' => '/segreteria/fir', 'tip' => 'Blocchi e formulari digitali', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>'],
                ['key' => 'rentri', 'label' => 'RENTRI', 'route' => 'segreteria.rentri', 'path' => '/segreteria/rentri', 'tip' => 'Trasmissione registro MASE', 'icon' => '<line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>'],
                ['key' => 'rentri-impostazioni', 'label' => 'Impostazioni RENTRI', 'route' => 'segreteria.impostazioni.rentri', 'path' => '/segreteria/impostazioni/rentri', 'tip' => 'Certificati e sandbox', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>'],
                ['key' => 'impostazioni-notifiche', 'label' => 'Notifiche email', 'route' => 'segreteria.impostazioni.notifiche', 'path' => '/segreteria/impostazioni/notifiche', 'tip' => 'Toggle eventi email stub', 'icon' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>'],
                ['key' => 'impostazioni-sicurezza', 'label' => 'Sicurezza 2FA', 'route' => 'segreteria.impostazioni.sicurezza', 'path' => '/segreteria/impostazioni/sicurezza', 'tip' => 'TOTP opt-in admin/segreteria', 'icon' => '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>'],
            ],
        ],
        [
            'label' => 'Amministrazione',
            'items' => [
                ['key' => 'ecommerce', 'label' => 'E-commerce', 'route' => 'segreteria.ecommerce', 'path' => '/segreteria/ecommerce', 'tip' => 'Catalogo ricambi', 'icon' => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>'],
                ['key' => 'mud', 'label' => 'MUD', 'route' => 'segreteria.mud', 'path' => '/segreteria/mud', 'tip' => 'Dichiarazioni ambientali', 'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>'],
            ],
        ],
    ];

    $demoActive = \App\Support\Demo\DemoContext::isActive();
@endphp

<aside class="seg-sidebar" id="seg-sidebar" aria-label="Menu" data-seg-tablet-sidebar="true">
    <div class="seg-sidebar-header">
        <a href="{{ Route::has('segreteria.dashboard') ? route('segreteria.dashboard') : url('/segreteria') }}" class="seg-sidebar-logo">
            <span><span class="seg-sidebar-logo-accent">ERP</span> VFU</span>
        </a>
        <button type="button" class="seg-sidebar-toggle" id="seg-toggle" aria-label="Comprimi menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
        </button>
    </div>

    @if ($demoActive)
        <div class="seg-sidebar-demo-badge" title="Scope demo attivo — dati produzione isolati">
            <span class="seg-badge seg-badge-warning">Palestra ON</span>
        </div>
    @endif

    <nav class="seg-sidebar-nav">
        @foreach ($groups as $group)
            <div class="seg-sidebar-group">
                <span class="seg-sidebar-group-label">{{ $group['label'] }}</span>
                @foreach ($group['items'] as $item)
                    @php
                        $href = Route::has($item['route']) ? route($item['route']) : url($item['path']);
                        $isActive = $active === $item['key'];
                    @endphp
                    <a href="{{ $href }}" @class(['active' => $isActive]) title="{{ $item['tip'] }}" @if($isActive) aria-current="page" @endif @if($item['key'] === 'rentri') data-tour="rentri-nav" @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    <div class="seg-sidebar-footer">
        @livewire(\App\Http\Livewire\Segreteria\DemoModeToggle::class)
        <p class="seg-footer-text">Segreteria</p>
        <p class="seg-footer-text">Versione 1.0</p>
    </div>

    <div style="padding: 0 16px 16px;">
        <form method="POST" action="{{ Route::has('logout') ? route('logout') : url('/logout') }}">
            @csrf
            <button type="submit" class="seg-logout-btn" title="Esci dall'applicazione">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Esci</span>
            </button>
        </form>
    </div>
</aside>
