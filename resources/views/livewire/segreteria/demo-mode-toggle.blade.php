<div class="demo-mode-toggle">
    @if ($canToggle)
        <div class="demo-mode-toggle-inner">
            <div class="demo-mode-toggle-label">
                <span class="demo-mode-toggle-title">Palestra operativa</span>
                <span class="demo-mode-toggle-hint">
                    @if ($sessionDemo)
                        Demo attiva
                    @else
                        Scope produzione
                    @endif
                </span>
            </div>

            @if ($sessionDemo)
                <button type="button"
                    class="demo-mode-toggle-btn demo-mode-toggle-btn-off"
                    wire:click="deactivate"
                    title="Disattiva palestra operativa">
                    ON
                </button>
            @elseif ($canActivate)
                <button type="button"
                    class="demo-mode-toggle-btn demo-mode-toggle-btn-on"
                    wire:click="requestActivate"
                    title="Attiva palestra operativa (sandbox MASE)">
                    OFF
                </button>
            @else
                <span class="demo-mode-toggle-locked" title="Abilitare ALLOW_SESSION_DEMO in production">🔒</span>
            @endif
        </div>

        @if ($sessionDemo)
            <a href="{{ route('segreteria.impostazioni.rentri') }}" class="demo-mode-settings-link">
                Impostazioni RENTRI demo
            </a>
        @endif

        @if ($showConfirmActivate)
            <div class="demo-mode-modal-backdrop" wire:click="cancelActivate">
                <div class="demo-mode-modal" wire:click.stop>
                    <h3 class="demo-mode-modal-title">Attivare la palestra operativa?</h3>
                    <p class="demo-mode-modal-text">
                        Il CRM passerà allo scope <strong>is_demo=true</strong>.
                        CER, blocchi FIR e vidima verranno letti da <strong>demoapi.rentri.gov.it</strong>
                        (ambiente DEMO MASE, mai produzione).
                        Serve il <strong>certificato PKCS#12 sandbox</strong> caricato in Impostazioni RENTRI.
                        I dati produzione restano isolati e non modificabili.
                    </p>
                    <div class="demo-mode-modal-actions">
                        <button type="button" class="seg-btn seg-btn-secondary" wire:click="cancelActivate">Annulla</button>
                        <button type="button" class="seg-btn seg-btn-primary" wire:click="confirmActivate">Attiva demo</button>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
