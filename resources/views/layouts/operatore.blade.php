<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="theme-color" content="#F2F2F7">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ config('operatore.pwa.short_name', 'Operatore') }}">
    <link rel="manifest" href="{{ route('operatore.manifest') }}">

    <title>{{ $title ?? config('app.name', 'ERP VFU') }} — Operatore</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="operatore-page">
    <div class="op-wrap">
        <header class="op-header">
            <div class="op-header-inner">
                <h1 class="op-header-title">{{ $headerTitle ?? 'Operatore' }}</h1>
                <div class="op-header-actions" style="display:flex;align-items:center;gap:0.5rem;">
                    <button
                        type="button"
                        class="op-search-btn"
                        aria-label="Apri ricerca globale"
                        title="Cerca (⌘K / Ctrl+K)"
                        x-on:click="$dispatch('open-global-search')"
                    >
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </button>
                    <livewire:global-search />
                    <livewire:notification-bell />
                <form method="POST" action="{{ Route::has('logout') ? route('logout') : url('/logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="op-btn-esci" title="Esci">Esci</button>
                </form>
                </div>
            </div>
        </header>

        <main class="op-main">
            {{ $slot }}
        </main>

        <nav class="op-bottom-nav pb-safe" aria-label="Navigazione principale">
            <div class="op-bottom-nav-inner">
                @php
                    $opNav = [
                        ['key' => 'dashboard',  'label' => 'Dashboard', 'route' => 'operatore.dashboard', 'path' => '/operatore', 'icon' => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'],
                        ['key' => 'bonifica',   'label' => 'Bonifica',  'route' => 'operatore.bonifica',  'path' => '/operatore/bonifica',  'icon' => 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z'],
                        ['key' => 'smontaggio', 'label' => 'Smontaggio','route' => 'operatore.smontaggio','path' => '/operatore/smontaggio','icon' => 'M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z M14 2v6h6 M16 13H8 M16 17H8 M10 9H8'],
                        ['key' => 'ricambi',    'label' => 'Ricambi',   'route' => 'operatore.ricambi',   'path' => '/operatore/ricambi',   'icon' => 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'],
                        ['key' => 'vetrina',    'label' => 'Vetrina',   'route' => 'operatore.vetrina',   'path' => '/operatore/vetrina',   'icon' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z'],
                    ];
                    $activeNav = $active ?? 'dashboard';
                @endphp
                @foreach ($opNav as $item)
                    @php
                        $href = Route::has($item['route']) ? route($item['route']) : url($item['path']);
                    @endphp
                    <a href="{{ $href }}" class="op-nav-item {{ $activeNav === $item['key'] ? 'active' : '' }}" @if($activeNav === $item['key']) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="{{ $item['icon'] }}"/></svg>
                        @if ($item['key'] === 'bonifica' && ($bonificaNavBadge ?? 0) > 0)
                            <span class="op-nav-badge" aria-label="{{ $bonificaNavBadge }} veicoli da bonificare">{{ $bonificaNavBadge > 99 ? '99+' : $bonificaNavBadge }}</span>
                        @elseif ($item['key'] === 'smontaggio' && ($smontaggioNavBadge ?? 0) > 0)
                            <span class="op-nav-badge" aria-label="{{ $smontaggioNavBadge }} veicoli da smontare">{{ $smontaggioNavBadge > 99 ? '99+' : $smontaggioNavBadge }}</span>
                        @endif
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    </div>

    @livewireScripts

    {{-- PWA install prompt banner --}}
    <div id="pwa-install-banner"
         x-data="pwaInstall()"
         x-show="showBanner"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         style="display:none;position:fixed;bottom:80px;left:12px;right:12px;z-index:200;
                background:#fff;border-radius:14px;box-shadow:0 8px 32px rgba(0,0,0,.14);
                padding:14px 16px;display:flex;align-items:center;gap:12px;">
        <div style="flex:1;">
            <p style="margin:0;font-size:14px;font-weight:600;color:#1c1c1e;">Installa l'app Operatore</p>
            <p style="margin:2px 0 0;font-size:12px;color:#636366;">Accesso rapido dal tuo dispositivo.</p>
        </div>
        <button @click="install()"
                style="background:#007AFF;color:#fff;border:none;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;">
            Installa
        </button>
        <button @click="dismiss()"
                style="background:none;border:none;color:#636366;cursor:pointer;padding:4px;font-size:20px;line-height:1;"
                aria-label="Chiudi">×</button>
    </div>

    <script>
        function pwaInstall() {
            return {
                showBanner: false,
                deferredPrompt: null,
                init() {
                    if (localStorage.getItem('pwa-install-dismissed')) return;

                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        this.deferredPrompt = e;
                        this.showBanner = true;
                    });

                    window.addEventListener('appinstalled', () => {
                        this.showBanner = false;
                        localStorage.setItem('pwa-install-dismissed', '1');
                    });
                },
                install() {
                    if (!this.deferredPrompt) return;
                    this.deferredPrompt.prompt();
                    this.deferredPrompt.userChoice.then((result) => {
                        if (result.outcome === 'accepted') {
                            localStorage.setItem('pwa-install-dismissed', '1');
                        }
                        this.deferredPrompt = null;
                        this.showBanner = false;
                    });
                },
                dismiss() {
                    this.showBanner = false;
                    localStorage.setItem('pwa-install-dismissed', '1');
                },
            };
        }

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/operatore-sw.js', { scope: '/operatore/' })
                    .catch(function (err) { console.warn('SW registration failed:', err); });
            });
        }

        document.addEventListener('keydown', (event) => {
            if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                window.dispatchEvent(new CustomEvent('open-global-search'));
            }
        });
    </script>
</body>
</html>
