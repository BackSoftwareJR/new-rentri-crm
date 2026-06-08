<div>
    @unless ($fullPage)
        <a href="{{ route('shop.carrello') }}" class="shop-btn shop-btn-secondary" wire:navigate>Vai al carrello</a>
        <button type="button" class="shop-cart-btn" wire:click="openDrawer" aria-label="Carrello">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            @if ($count > 0)
                <span class="shop-cart-badge">{{ $count > 99 ? '99+' : $count }}</span>
            @endif
        </button>
    @endunless

    @if ($open)
        @unless ($fullPage)
            <div class="shop-drawer-overlay" wire:click="closeDrawer"></div>
        @endunless
        <aside class="{{ $fullPage ? 'shop-detail-panel' : 'shop-drawer' }}" role="dialog" aria-label="Carrello">
            <div class="shop-drawer-header">
                <h2>Carrello</h2>
                @unless ($fullPage)
                    <button type="button" class="shop-drawer-close" wire:click="closeDrawer" aria-label="Chiudi">×</button>
                @else
                    <a href="{{ route('shop.index') }}" class="shop-btn shop-btn-secondary" wire:navigate>← Catalogo</a>
                @endunless
            </div>

            @if (session('cart_error'))
                <p class="shop-alert shop-alert--error">{{ session('cart_error') }}</p>
            @endif
            @if (session('cart_success'))
                <p class="shop-alert shop-alert--ok">{{ session('cart_success') }}</p>
            @endif

            @if ($lines->isEmpty())
                <p class="shop-placeholder" style="padding: 24px 0;">Il carrello è vuoto.</p>
            @else
                <ul class="shop-cart-list">
                    @foreach ($lines as $line)
                        @php $p = $line['prodotto']; @endphp
                        <li class="shop-cart-item" wire:key="drawer-{{ $p->id }}">
                            <div class="shop-cart-item-info">
                                <strong>{{ $p->nome }}</strong>
                                <span>€ {{ number_format((float) $p->prezzo, 2, ',', '.') }}</span>
                            </div>
                            <div class="shop-cart-item-actions">
                                <input
                                    type="number"
                                    min="1"
                                    max="{{ $p->giacenza + $line['qty'] }}"
                                    value="{{ $line['qty'] }}"
                                    wire:change="updateQty({{ $p->id }}, $event.target.value)"
                                    class="shop-qty-input"
                                />
                                <button type="button" class="shop-btn-link" wire:click="remove({{ $p->id }})">Rimuovi</button>
                            </div>
                            <div class="shop-cart-item-sub">€ {{ number_format($line['subtotale'], 2, ',', '.') }}</div>
                        </li>
                    @endforeach
                </ul>

                <div class="shop-drawer-footer">
                    <p class="shop-cart-total">Totale: <strong>€ {{ number_format($totale, 2, ',', '.') }}</strong></p>
                    <a href="{{ route('shop.checkout') }}" class="shop-btn shop-btn-primary shop-btn-block" wire:navigate wire:click="closeDrawer">
                        Procedi all'acquisto
                    </a>
                </div>
            @endif
        </aside>
    @endif
</div>
