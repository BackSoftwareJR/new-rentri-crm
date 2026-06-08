@php
    $user = auth()->user();
    $enforcement = app(\App\Domain\Auth\TwoFactorEnforcementService::class);
@endphp

@if ($user && $enforcement->shouldShowGraceBanner($user))
    <div class="seg-alert-banner seg-card-padding-sm" role="alert" style="margin-bottom: 0; border-left: 4px solid var(--seg-warning, #f59e0b);">
        <strong>2FA obbligatoria in arrivo</strong>
        <span class="seg-muted-inline">— {{ $enforcement->graceBannerMessage() }}</span>
        <a href="{{ route('segreteria.impostazioni.sicurezza') }}" class="seg-btn seg-btn-secondary seg-btn-sm" style="margin-left: 0.75rem;" wire:navigate>
            Configura 2FA
        </a>
    </div>
@elseif ($user && $enforcement->requiresTwoFactorSetup($user) && request()->routeIs('segreteria.impostazioni.sicurezza'))
    <div class="seg-alert-banner seg-card-padding-sm" role="alert" style="margin-bottom: 0; border-left: 4px solid var(--seg-danger, #dc2626);">
        <strong>2FA obbligatoria</strong>
        <span class="seg-muted-inline">— {{ $enforcement->redirectMessage() }}</span>
    </div>
@endif
