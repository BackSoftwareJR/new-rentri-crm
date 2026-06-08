<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <p class="mag-back">
                <a href="{{ route('segreteria.ecommerce') }}" wire:navigate>← Catalogo</a>
            </p>
            <h1>Carrello</h1>
            <p>Carrello — crea ordine bozza e completa il checkout dal dettaglio ordine.</p>
        </div>
        <div class="seg-header-actions">
            <x-ecommerce-payment-mode-badge />
        </div>
    </div>

    @if (! $paymentRuntime->isStub() && count($paymentPreflight) > 0 && ! $paymentPreflightOk)
        <div class="seg-card seg-card-padding-sm seg-alert-banner" role="status">
            <strong>Stripe non configurato</strong>
            <span class="seg-muted-inline">— impostare STRIPE_KEY, STRIPE_WEBHOOK_SECRET e STRIPE_CURRENCY=eur per checkout {{ $paymentRuntime->isStripeProduction() ? 'produzione' : 'sandbox' }}.</span>
        </div>
    @endif

    @if (! $paymentRuntime->isStub() && count($paymentPreflight) > 0)
        <div class="seg-card seg-card-padding-sm" role="status">
            <strong>Stripe {{ $paymentRuntime->isStripeProduction() ? 'produzione' : 'sandbox' }}</strong>
            · <a href="{{ $stripeDashboardUrl }}" target="_blank" rel="noopener noreferrer">Dashboard Stripe</a>
        </div>
    @endif

    @if ($lines->isEmpty())
        <div class="seg-card seg-card-padding">
            <p class="mag-empty">Il carrello è vuoto.</p>
            <a href="{{ route('segreteria.ecommerce') }}" class="seg-btn seg-btn-primary" wire:navigate>Sfoglia catalogo</a>
        </div>
    @else
        <div class="seg-card seg-card-padding-none">
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th>Codice</th>
                            <th>Prodotto</th>
                            <th>Prezzo</th>
                            <th>Qty</th>
                            <th>Subtotale</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $line)
                            @php $p = $line['prodotto']; @endphp
                            <tr wire:key="cart-{{ $p->id }}">
                                <td>{{ $p->codice }}</td>
                                <td>{{ $p->nome }}</td>
                                <td>{{ $service->prezzoDisplay($p) }}</td>
                                <td>
                                    <input type="number" min="0" max="{{ $p->giacenza + $line['qty'] }}" value="{{ $line['qty'] }}"
                                        wire:change="aggiornaQty({{ $p->id }}, $event.target.value)"
                                        class="seg-input" style="width: 4rem;" />
                                </td>
                                <td>{{ number_format($line['subtotale'], 2, ',', '.') }} €</td>
                                <td>
                                    <button type="button" class="seg-btn seg-btn-secondary seg-btn-sm" wire:click="rimuovi({{ $p->id }})">Rimuovi</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="seg-card seg-card-padding" style="margin-top: 1rem;">
            <p class="seg-cell-strong" style="font-size: 1.1rem;">Totale: {{ number_format($totale, 2, ',', '.') }} €</p>
            <button type="button" class="seg-btn seg-btn-primary" wire:click="creaOrdineBozza" wire:confirm="Creare ordine bozza? La giacenza verrà scalata." wire:loading.attr="disabled">
                Crea ordine bozza
            </button>
        </div>
    @endif
</div>
