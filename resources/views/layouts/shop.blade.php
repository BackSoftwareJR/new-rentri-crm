<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Ricambi' }} — {{ config('app.name', 'ERP VFU') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        .shop-layout { font-family: Inter, system-ui, sans-serif; background: #f5f5f7; color: #1d1d1f; min-height: 100vh; }
        .shop-header { background: rgba(255,255,255,.82); backdrop-filter: blur(12px); border-bottom: 1px solid #e5e5ea; position: sticky; top: 0; z-index: 50; }
        .shop-header-inner { max-width: 1120px; margin: 0 auto; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .shop-brand { font-size: 18px; font-weight: 600; color: #1d1d1f; text-decoration: none; }
        .shop-main { max-width: 1120px; margin: 0 auto; padding: 28px 20px 48px; }
        .shop-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; }
        .shop-card { background: #fff; border-radius: 18px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); transition: transform .2s, box-shadow .2s; color: inherit; display: block; }
        .shop-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        .shop-card-link { text-decoration: none; color: inherit; display: block; }
        .shop-card-img { aspect-ratio: 4/3; background: #f2f2f7; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .shop-card-img img { width: 100%; height: 100%; object-fit: cover; }
        .shop-card-body { padding: 16px; }
        .shop-card-title { font-size: 15px; font-weight: 600; margin: 0 0 6px; line-height: 1.3; }
        .shop-card-price { font-size: 17px; font-weight: 700; color: #1d1d1f; }
        .shop-card-meta { font-size: 12px; color: #86868b; margin-top: 4px; }
        .shop-filters { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
        .shop-input, .shop-select { border: 1px solid #d2d2d7; border-radius: 10px; padding: 10px 14px; font-size: 14px; background: #fff; min-width: 200px; }
        .shop-detail { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
        @media (max-width: 768px) { .shop-detail { grid-template-columns: 1fr; } }
        .shop-detail-img { background: #fff; border-radius: 20px; overflow: hidden; aspect-ratio: 1; display: flex; align-items: center; justify-content: center; }
        .shop-detail-img img { width: 100%; height: 100%; object-fit: cover; }
        .shop-detail-panel { background: #fff; border-radius: 20px; padding: 28px; }
        .shop-detail-title { font-size: 28px; font-weight: 700; margin: 0 0 8px; letter-spacing: -.02em; }
        .shop-detail-price { font-size: 24px; font-weight: 700; margin: 12px 0; }
        .shop-badge { display: inline-block; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 999px; background: #f2f2f7; color: #636366; }
        .shop-badge--ok { background: #e8f8ee; color: #1b7f3a; }
        .shop-badge--out { background: #fdecea; color: #c41e1e; }
        .shop-cta { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
        .shop-btn { display: inline-flex; align-items: center; justify-content: center; padding: 12px 20px; border-radius: 12px; font-size: 15px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
        .shop-btn-primary { background: #0071e3; color: #fff; }
        .shop-btn-secondary { background: #f2f2f7; color: #1d1d1f; }
        .shop-placeholder { color: #aeaeb2; font-size: 14px; }
        .shop-header-actions { display: flex; align-items: center; gap: 12px; }
        .shop-cart-btn { position: relative; background: #f2f2f7; border: none; border-radius: 12px; padding: 10px 12px; cursor: pointer; color: #1d1d1f; display: inline-flex; align-items: center; }
        .shop-cart-badge { position: absolute; top: -4px; right: -4px; background: #0071e3; color: #fff; font-size: 11px; font-weight: 700; min-width: 18px; height: 18px; border-radius: 999px; display: flex; align-items: center; justify-content: center; padding: 0 4px; }
        .shop-drawer-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.35); z-index: 60; }
        .shop-drawer { position: fixed; top: 0; right: 0; width: min(400px, 100vw); height: 100vh; background: #fff; z-index: 70; box-shadow: -8px 0 32px rgba(0,0,0,.12); display: flex; flex-direction: column; padding: 20px; }
        .shop-drawer-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .shop-drawer-header h2 { margin: 0; font-size: 20px; font-weight: 700; }
        .shop-drawer-close { background: none; border: none; font-size: 28px; line-height: 1; cursor: pointer; color: #86868b; }
        .shop-cart-list { list-style: none; margin: 0; padding: 0; flex: 1; overflow-y: auto; }
        .shop-cart-item { padding: 14px 0; border-bottom: 1px solid #f2f2f7; }
        .shop-cart-item-info { display: flex; justify-content: space-between; gap: 8px; font-size: 14px; }
        .shop-cart-item-actions { display: flex; align-items: center; gap: 10px; margin-top: 8px; }
        .shop-qty-input { width: 56px; border: 1px solid #d2d2d7; border-radius: 8px; padding: 6px 8px; font-size: 14px; }
        .shop-btn-link { background: none; border: none; color: #0071e3; font-size: 13px; cursor: pointer; text-decoration: underline; }
        .shop-cart-item-sub { font-size: 13px; color: #636366; margin-top: 4px; text-align: right; }
        .shop-drawer-footer { margin-top: auto; padding-top: 16px; border-top: 1px solid #f2f2f7; }
        .shop-cart-total { font-size: 17px; margin: 0 0 12px; }
        .shop-btn-block { width: 100%; }
        .shop-alert { padding: 10px 14px; border-radius: 10px; font-size: 14px; margin-bottom: 12px; }
        .shop-alert--error { background: #fdecea; color: #c41e1e; }
        .shop-alert--ok { background: #e8f8ee; color: #1b7f3a; }
        .shop-form-group { margin-bottom: 14px; }
        .shop-form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #636366; }
        .shop-input--block { width: 100%; min-width: 0; box-sizing: border-box; }
        .shop-field-error { color: #c41e1e; font-size: 12px; }
        .shop-checkout-steps { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
        .shop-step { font-size: 13px; font-weight: 600; padding: 6px 12px; border-radius: 999px; background: #f2f2f7; color: #86868b; }
        .shop-step--active { background: #0071e3; color: #fff; }
        .shop-stub-card { background: #f9f9fb; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
        .shop-card-actions { padding: 0 16px 16px; }
        .shop-btn-add { width: 100%; margin-top: 8px; }
        .shop-breadcrumb { margin: 0 0 16px; font-size: 14px; color: #86868b; }
        .shop-breadcrumb a { color: #0071e3; text-decoration: none; }
        .shop-breadcrumb a:hover { text-decoration: underline; }
    </style>
</head>
<body class="shop-layout">
    <header class="shop-header">
        <div class="shop-header-inner">
            <a href="{{ route('shop.index') }}" class="shop-brand" wire:navigate>{{ config('app.name', 'ERP VFU') }} Ricambi</a>
            <div class="shop-header-actions">
                <a href="{{ route('shop.index') }}" class="shop-btn shop-btn-secondary" wire:navigate>Catalogo</a>
                @unless (request()->routeIs('shop.carrello'))
                    @livewire('shop-cart')
                @endunless
            </div>
        </div>
    </header>
    <main class="shop-main">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
