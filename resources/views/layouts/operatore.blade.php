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
                <form method="POST" action="{{ Route::has('logout') ? route('logout') : url('/logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="op-btn-esci" title="Esci">Esci</button>
                </form>
            </div>
        </header>

        <main class="op-main">
            {{ $slot }}
        </main>

        <nav class="op-bottom-nav" aria-label="Navigazione principale">
            <div class="op-bottom-nav-inner">
                @php
                    $opNav = [
                        ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'operatore.dashboard', 'path' => '/operatore', 'icon' => 'M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'],
                        ['key' => 'bonifica', 'label' => 'Bonifica', 'route' => 'operatore.bonifica', 'path' => '/operatore/bonifica', 'icon' => 'M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z'],
                        ['key' => 'ricambi', 'label' => 'Ricambi', 'route' => 'operatore.ricambi', 'path' => '/operatore/ricambi', 'icon' => 'M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z'],
                        ['key' => 'vetrina', 'label' => 'Vetrina', 'route' => 'operatore.vetrina', 'path' => '/operatore/vetrina', 'icon' => 'M3 3h7v7H3z M14 3h7v7h-7z M3 14h7v7H3z M14 14h7v7h-7z'],
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
                        @endif
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>
    </div>

    @livewireScripts
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/operatore-sw.js', { scope: '/operatore/' }).catch(function () {});
            });
        }
    </script>
</body>
</html>
