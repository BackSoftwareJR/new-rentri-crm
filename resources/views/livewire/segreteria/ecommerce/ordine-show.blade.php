<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions">
        <div>
            <p class="mag-back">
                <a href="{{ route('segreteria.ecommerce') }}" wire:navigate>← Catalogo</a>
            </p>
            <h1>Ordine #{{ $ordine->id }}</h1>
            <p>
                <x-badge-stato :stato="$service->statoOrdineBadge($ordine->stato)" :label="$service->statoOrdineLabel($ordine->stato)" />
                <x-ecommerce-payment-mode-badge />
                @if ($ordine->pagamento_metodo)
                    — {{ ucfirst(str_replace('_', ' ', $ordine->pagamento_metodo)) }}
                @endif
            </p>
        </div>
    </div>

    <div class="seg-kpi-grid mag-detail-kpi">
        <x-kpi-card title="Totale" :value="number_format((float) $ordine->totale, 2, ',', '.') . ' €'" />
        <x-kpi-card title="Righe" :value="(string) count($ordine->righe ?? [])" />
        <x-kpi-card title="Cliente" :value="$ordine->user?->name ?? '—'" />
        <x-kpi-card title="Creato il" :value="$ordine->created_at->format('d/m/Y H:i')" />
    </div>

    @if (! $paymentRuntime->isStub() && count($paymentPreflight) > 0)
        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">Preflight Stripe</h2>
            <p class="seg-text-muted">
                Dashboard:
                <a href="{{ $stripeDashboardUrl }}" target="_blank" rel="noopener noreferrer">Stripe {{ $paymentRuntime->isStripeProduction() ? 'produzione' : 'sandbox' }}</a>
            </p>
            <ul class="seg-doc-list seg-mb-md">
                @foreach ($paymentPreflight as $item)
                    <li wire:key="pay-pref-{{ $item['key'] }}">
                        @if ($item['ok'])
                            <x-badge-stato stato="success" label="OK" />
                        @else
                            <x-badge-stato stato="danger" label="KO" />
                        @endif
                        {{ $item['label'] }}
                        @if (! $item['ok'] && $item['hint'])
                            <span class="seg-muted-inline">— {{ $item['hint'] }}</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($ordine->stato === \App\Enums\OrdineEcommerceStato::Bozza)
        <section class="seg-card seg-card-padding">
            <h2 class="mag-section-title">
                Checkout sicuro
                @if ($paymentRuntime->isStub())
                    (stub token)
                @else
                    (Stripe Checkout)
                @endif
            </h2>
            <p class="mag-section-lead">
                @if ($paymentRuntime->isStub())
                    Avvia il checkout: viene generato un token monouso per confermare il pagamento.
                @else
                    Avvia il checkout Stripe: verrai reindirizzato al pagamento sicuro; la conferma arriva via webhook.
                @endif
            </p>
            <div class="seg-form-grid">
                <div class="seg-form-group">
                    <label class="seg-label" for="pagamento-metodo">Metodo pagamento</label>
                    <select id="pagamento-metodo" wire:model="pagamentoMetodo" class="seg-select">
                        <option value="bonifico">Bonifico bancario</option>
                        <option value="contanti">Contanti (ritiro)</option>
                        @if ($paymentRuntime->isStub())
                            <option value="pos_stub">POS — stub gateway</option>
                        @else
                            <option value="stripe">Carta — Stripe Checkout</option>
                        @endif
                    </select>
                </div>
                <div class="seg-form-group seg-form-group--span2">
                    <label class="seg-label" for="note-checkout">Note</label>
                    <input id="note-checkout" type="text" wire:model="noteCheckout" class="seg-input" placeholder="Riferimento ordine, note consegna…" />
                </div>
            </div>
            <div class="seg-header-actions" style="margin-top: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                @can('checkout', $ordine)
                    <button type="button" class="seg-btn seg-btn-primary" wire:click="avviaCheckout"
                        wire:loading.attr="disabled"
                        @disabled(! $paymentRuntime->isStub() && ! $paymentPreflightOk)>
                        Avvia checkout
                    </button>
                @endcan
                @can('annulla', $ordine)
                    <button type="button" class="seg-btn seg-btn-secondary" wire:click="annullaOrdine" wire:confirm="Annullare l'ordine e ripristinare la giacenza?" wire:loading.attr="disabled">Annulla ordine</button>
                @endcan
            </div>
        </section>
    @endif

    @if ($ordine->stato === \App\Enums\OrdineEcommerceStato::PagamentoInAttesa)
        @if ($ordine->payment_gateway === 'stripe' && $ordine->payment_checkout_url)
            <section class="seg-card seg-card-padding">
                <h2 class="mag-section-title">Pagamento Stripe</h2>
                <p class="mag-section-lead">Completa il pagamento sulla pagina sicura Stripe. L'ordine verrà confermato automaticamente al webhook.</p>
                <a href="{{ $ordine->payment_checkout_url }}" class="seg-btn seg-btn-primary" target="_blank" rel="noopener">
                    Vai al pagamento Stripe
                </a>
                @can('annulla', $ordine)
                    <button type="button" class="seg-btn seg-btn-secondary" wire:click="annullaOrdine" wire:confirm="Annullare l'ordine?" wire:loading.attr="disabled" style="margin-left: 0.5rem;">Annulla ordine</button>
                @endcan
            </section>
        @else
            <section class="seg-card seg-card-padding seg-eco-checkout-token">
                <h2 class="mag-section-title">Conferma pagamento</h2>
                <p class="mag-section-lead">Inserisci il token checkout per simulare la callback del gateway (stub sicuro).</p>
                @if ($ordine->checkout_token)
                    <p class="seg-text-muted">Token generato: <code>{{ $ordine->checkout_token }}</code></p>
                @endif
                <div class="seg-form-group" style="max-width: 420px;">
                    <label class="seg-label" for="checkout-token">Token checkout (32 caratteri)</label>
                    <input id="checkout-token" type="text" wire:model="checkoutToken" maxlength="32" class="seg-input" autocomplete="off" />
                    @error('checkoutToken') <p class="seg-field-error">{{ $message }}</p> @enderror
                </div>
                <div class="seg-header-actions" style="margin-top: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                    @can('checkout', $ordine)
                        <button type="button" class="seg-btn seg-btn-primary" wire:click="confermaPagamento" wire:loading.attr="disabled">Conferma pagamento</button>
                    @endcan
                    @can('annulla', $ordine)
                        <button type="button" class="seg-btn seg-btn-secondary" wire:click="annullaOrdine" wire:confirm="Annullare l'ordine?" wire:loading.attr="disabled">Annulla ordine</button>
                    @endcan
                </div>
            </section>
        @endif
    @endif

    @if ($ordine->note_checkout)
        <p class="seg-text-muted" style="margin: 0 0 1rem;">Note checkout: {{ $ordine->note_checkout }}</p>
    @endif

    <div class="seg-card seg-card-padding-none">
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th>Codice</th>
                        <th>Prodotto</th>
                        <th>Qty</th>
                        <th>Prezzo unit.</th>
                        <th>Subtotale</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ordine->righe ?? [] as $riga)
                        <tr wire:key="ord-riga-{{ $loop->index }}">
                            <td>{{ $riga['codice'] ?? '—' }}</td>
                            <td>{{ $riga['nome'] ?? '—' }}</td>
                            <td>{{ $riga['qty'] ?? 0 }}</td>
                            <td>{{ number_format((float) ($riga['prezzo_unitario'] ?? 0), 2, ',', '.') }} €</td>
                            <td>{{ number_format((float) ($riga['subtotale'] ?? 0), 2, ',', '.') }} €</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
