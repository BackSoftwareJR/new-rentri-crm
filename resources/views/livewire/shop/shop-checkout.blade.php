<div>
    <a href="{{ route('shop.index') }}" class="shop-btn shop-btn-secondary" style="margin-bottom: 20px; display: inline-flex;" wire:navigate>← Catalogo</a>

    @if (session('checkout_error'))
        <p class="shop-alert shop-alert--error">{{ session('checkout_error') }}</p>
    @endif

    <div class="shop-checkout-steps">
        <span class="shop-step {{ $step >= 1 ? 'shop-step--active' : '' }}">1. Dati cliente</span>
        <span class="shop-step {{ $step >= 2 ? 'shop-step--active' : '' }}">2. Pagamento</span>
        <span class="shop-step {{ $step >= 3 ? 'shop-step--active' : '' }}">3. Conferma</span>
    </div>

    @if ($step === 1)
        <div class="shop-detail-panel" style="max-width: 520px;">
            <h1 class="shop-detail-title" style="font-size: 22px;">I tuoi dati</h1>
            <p style="color: #86868b; font-size: 14px; margin-bottom: 20px;">
                @auth
                    Acquisto con account collegato.
                @else
                    Checkout come ospite — nessun account richiesto.
                @endauth
            </p>

            <div class="shop-form-group">
                <label for="nome">Nome e cognome</label>
                <input id="nome" type="text" wire:model="nome" class="shop-input shop-input--block" autocomplete="name" />
                @error('nome') <span class="shop-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="shop-form-group">
                <label for="email">Email</label>
                <input id="email" type="email" wire:model="email" class="shop-input shop-input--block" autocomplete="email" />
                @error('email') <span class="shop-field-error">{{ $message }}</span> @enderror
            </div>
            <div class="shop-form-group">
                <label for="telefono">Telefono</label>
                <input id="telefono" type="tel" wire:model="telefono" class="shop-input shop-input--block" autocomplete="tel" />
                @error('telefono') <span class="shop-field-error">{{ $message }}</span> @enderror
            </div>

            <button type="button" class="shop-btn shop-btn-primary" wire:click="proceedToPayment" wire:loading.attr="disabled">
                Continua al pagamento
            </button>
        </div>
    @elseif ($step === 2)
        <div class="shop-detail-panel" style="max-width: 520px;">
            <h1 class="shop-detail-title" style="font-size: 22px;">Pagamento</h1>

            @if ($runtime->isStub())
                <p style="color: #86868b; font-size: 14px; margin-bottom: 16px;">
                    Modalità test — usa la carta <code>4242 4242 4242 4242</code> e conferma con il token generato.
                </p>

                <div class="shop-stub-card">
                    <div class="shop-form-group">
                        <label>Numero carta (test)</label>
                        <input type="text" value="4242 4242 4242 4242" readonly class="shop-input shop-input--block" />
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <div class="shop-form-group" style="flex: 1;">
                            <label>Scadenza</label>
                            <input type="text" value="12/34" readonly class="shop-input shop-input--block" />
                        </div>
                        <div class="shop-form-group" style="flex: 1;">
                            <label>CVC</label>
                            <input type="text" value="123" readonly class="shop-input shop-input--block" />
                        </div>
                    </div>
                </div>

                @if ($ordine?->checkout_token)
                    <p style="font-size: 13px; color: #636366; margin: 12px 0;">
                        Token checkout: <code>{{ $ordine->checkout_token }}</code>
                    </p>
                @endif

                <div class="shop-form-group">
                    <label for="checkoutToken">Token checkout</label>
                    <input id="checkoutToken" type="text" wire:model="checkoutToken" class="shop-input shop-input--block" maxlength="32" />
                    @error('checkoutToken') <span class="shop-field-error">{{ $message }}</span> @enderror
                </div>

                <button type="button" class="shop-btn shop-btn-primary" wire:click="confirmStubPayment" wire:loading.attr="disabled">
                    Conferma pagamento test
                </button>
            @else
                <p style="color: #86868b; font-size: 14px;">
                    Reindirizzamento a Stripe per il pagamento sicuro…
                </p>
                @if ($ordine?->payment_checkout_url)
                    <a href="{{ $ordine->payment_checkout_url }}" class="shop-btn shop-btn-primary">
                        Paga con carta (Stripe)
                    </a>
                @endif

                @if (filled($stripeKey))
                    <div id="payment-element" style="margin-top: 16px;"></div>
                @endif
            @endif
        </div>
    @else
        <div class="shop-detail-panel" style="max-width: 520px; text-align: center;">
            <div style="font-size: 48px; margin-bottom: 12px;">✓</div>
            <h1 class="shop-detail-title" style="font-size: 24px;">Ordine confermato</h1>
            @if ($ordineId)
                <p style="font-size: 17px; margin: 16px 0;">
                    Numero ordine: <strong>#{{ $ordineId }}</strong>
                </p>
            @endif
            <p style="color: #86868b; font-size: 14px;">
                Riceverai una conferma all'indirizzo <strong>{{ $email }}</strong>.
            </p>
            <a href="{{ route('shop.index') }}" class="shop-btn shop-btn-primary" style="margin-top: 24px;" wire:navigate>
                Torna al catalogo
            </a>
        </div>
    @endif
</div>
