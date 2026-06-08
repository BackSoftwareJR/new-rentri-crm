<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>try{var t=localStorage.getItem('seg-theme');if(t==='dark'||t==='light'||t==='high-contrast')document.documentElement.dataset.theme=t}catch(e){}</script>

    <title>{{ $title ?? config('app.name', 'ERP VFU') }} — Segreteria</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
    @stack('head-extras')
</head>
<body class="segreteria-layout" data-seg-layout="segreteria">
    <div class="seg-wrap">
        <x-sidebar-nav :active="$active ?? null" />

        <div class="seg-main">
            <x-demo-banner />
            <x-two-factor-enforcement-banner />
            <x-topbar
                :breadcrumb="$breadcrumb ?? 'Home'"
                :current="$current ?? null"
                :role="$role ?? 'Segreteria'"
                :user="$user ?? auth()->user()?->name ?? 'Utente'"
            />

            <main class="seg-content">
                {{ $slot }}
            </main>
        </div>
    </div>

    @stack('foot-scripts')
    @livewireScripts
</body>
</html>
