<div>
    @include('livewire.partials.flash-messages')

    <p class="op-section-lead">Dati account operatore (email non modificabile).</p>

    <div class="op-card" style="padding: 20px;">
        <form wire:submit="salva" class="op-profilo-form">
            <div style="margin-bottom: 16px;">
                <label for="profilo-name" style="display: block; font-size: 14px; font-weight: 600; color: #3c3c43; margin-bottom: 6px;">Nome</label>
                <input id="profilo-name" type="text" wire:model="name" class="op-bn-search" autocomplete="name" />
                @error('name')
                    <p style="color: #dc2626; font-size: 13px; margin-top: 4px;">{{ $message }}</p>
                @enderror
            </div>

            <div style="margin-bottom: 20px;">
                <label for="profilo-email" style="display: block; font-size: 14px; font-weight: 600; color: #3c3c43; margin-bottom: 6px;">Email</label>
                <input id="profilo-email" type="email" value="{{ $email }}" class="op-bn-search" readonly disabled style="background: #f2f2f7; color: #8e8e93;" />
            </div>

            <button type="submit" class="op-btn op-btn-primary op-btn-full" wire:loading.attr="disabled">
                Salva modifiche
            </button>
        </form>
    </div>

    @if ($pushEnabled)
        <div class="op-card" style="padding: 20px; margin-top: 16px;">
            <h2 style="font-size: 16px; font-weight: 600; margin: 0 0 8px;">Notifiche push</h2>
            <p style="font-size: 14px; color: #636366; margin: 0 0 16px;">
                Ricevi avvisi sul dispositivo anche con l'app in background.
            </p>
            @error('push')
                <p style="color: #dc2626; font-size: 13px; margin-bottom: 12px;">{{ $message }}</p>
            @enderror
            <button
                type="button"
                id="op-push-subscribe-btn"
                class="op-btn op-btn-primary op-btn-full"
                data-vapid-key="{{ $vapidPublicKey }}"
            >
                Attiva notifiche push
            </button>
            <p id="op-push-status" style="font-size: 13px; color: #636366; margin: 12px 0 0;"></p>
        </div>

        <script>
            (function () {
                const btn = document.getElementById('op-push-subscribe-btn');
                const status = document.getElementById('op-push-status');
                if (!btn || !('Notification' in window) || !('serviceWorker' in navigator)) {
                    if (status) status.textContent = 'Push non supportate su questo browser.';
                    if (btn) btn.disabled = true;
                    return;
                }

                function urlBase64ToUint8Array(base64String) {
                    const padding = '='.repeat((4 - base64String.length % 4) % 4);
                    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                    const raw = window.atob(base64);
                    const output = new Uint8Array(raw.length);
                    for (let i = 0; i < raw.length; ++i) output[i] = raw.charCodeAt(i);
                    return output;
                }

                btn.addEventListener('click', async function () {
                    btn.disabled = true;
                    status.textContent = 'Richiesta permesso…';
                    try {
                        const permission = await Notification.requestPermission();
                        if (permission !== 'granted') {
                            status.textContent = 'Permesso notifiche negato.';
                            btn.disabled = false;
                            return;
                        }
                        const registration = await navigator.serviceWorker.ready;
                        const vapidKey = btn.dataset.vapidKey;
                        const subscription = await registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlBase64ToUint8Array(vapidKey),
                        });
                        @this.call('subscribePush', JSON.stringify(subscription.toJSON()), navigator.userAgent.slice(0, 100));
                        status.textContent = 'Notifiche push attivate.';
                    } catch (err) {
                        status.textContent = 'Errore attivazione push: ' + (err?.message || 'sconosciuto');
                        btn.disabled = false;
                    }
                });
            })();
        </script>
    @endif
</div>
