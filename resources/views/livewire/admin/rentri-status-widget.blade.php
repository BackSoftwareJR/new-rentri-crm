<div>
    @php
        $certColor = match(true) {
            $certDays === null       => '#64748b',
            $certDays < 0            => '#dc2626',
            $certDays < 15           => '#dc2626',
            $certDays < 30           => '#d97706',
            default                  => '#16a34a',
        };
        $certLabel = match(true) {
            $certDays === null  => 'N/D',
            $certDays < 0       => 'SCADUTO',
            default             => $certDays.' gg',
        };

        $firmaColor = match(true) {
            $firmaDays === null  => '#64748b',
            $firmaDays < 0      => '#dc2626',
            $firmaDays < 15     => '#dc2626',
            $firmaDays < 30     => '#d97706',
            default             => '#16a34a',
        };
        $firmaLabel = match(true) {
            $firmaDays === null  => 'N/D',
            $firmaDays < 0      => 'SCADUTO',
            default             => $firmaDays.' gg',
        };
    @endphp

    <div class="seg-card seg-card-padding" style="margin-bottom: 16px;">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;">
            <h3 style="margin: 0; font-size: 15px; font-weight: 600;">Stato RENTRI</h3>
            <span class="seg-badge" style="font-size: 11px; background: {{ $ambiente === 'live' ? '#dcfce7' : '#fef9c3' }}; color: {{ $ambiente === 'live' ? '#166534' : '#92400e' }};">
                {{ strtoupper($ambiente) }}
            </span>
            <button
                wire:click="refreshHealthCheck"
                wire:loading.attr="disabled"
                class="seg-btn seg-btn-ghost seg-btn-sm"
                style="margin-left: auto;"
                title="Esegui health check ora"
            >
                <span wire:loading.remove wire:target="refreshHealthCheck">⟳ Refresh</span>
                <span wire:loading wire:target="refreshHealthCheck">…</span>
            </button>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px;">

            {{-- Health check --}}
            <div style="background: #f8fafc; border-radius: 6px; padding: 10px 12px;">
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin-bottom: 4px;">Health check</div>
                <div style="font-size: 20px; font-weight: 700; color: {{ $healthOk ? '#16a34a' : '#dc2626' }};">
                    {{ $healthOk ? '✅ OK' : '❌ KO' }}
                </div>
                @if ($healthCheckedAt)
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 3px;">
                        {{ $healthCheckedAt->diffForHumans() }}
                    </div>
                @else
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 3px;">Non eseguito</div>
                @endif
                @if ($healthMessage && ! $healthOk)
                    <div style="font-size: 11px; color: #dc2626; margin-top: 3px; word-break: break-word;">
                        {{ Str::limit($healthMessage, 60) }}
                    </div>
                @endif
            </div>

            {{-- mTLS cert --}}
            <div style="background: #f8fafc; border-radius: 6px; padding: 10px 12px;">
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin-bottom: 4px;">Cert mTLS scade tra</div>
                <div style="font-size: 20px; font-weight: 700; color: {{ $certColor }};">
                    {{ $certLabel }}
                </div>
                @if ($certScadenza)
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 3px;">
                        {{ $certScadenza->format('d/m/Y') }}
                    </div>
                @endif
            </div>

            {{-- Firma cert --}}
            <div style="background: #f8fafc; border-radius: 6px; padding: 10px 12px;">
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin-bottom: 4px;">Cert firma xFIR scade tra</div>
                <div style="font-size: 20px; font-weight: 700; color: {{ $firmaColor }};">
                    {{ $firmaLabel }}
                </div>
                @if ($firmaScadenza)
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 3px;">
                        {{ $firmaScadenza->format('d/m/Y') }}
                    </div>
                @endif
            </div>

            {{-- Last trasmissione --}}
            <div style="background: #f8fafc; border-radius: 6px; padding: 10px 12px;">
                <div style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #64748b; margin-bottom: 4px;">Ultima trasmissione</div>
                @if ($lastTrasmissione)
                    <div style="font-size: 13px; font-weight: 600; color: #1e293b;">
                        {{ $lastTrasmissione->created_at->format('d/m/Y H:i') }}
                    </div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 3px;">
                        {{ $lastTrasmissione->stato ?? '—' }}
                    </div>
                @else
                    <div style="font-size: 13px; color: #94a3b8;">Nessuna</div>
                @endif
            </div>

        </div>

        {{-- Warning banner if any cert expires soon --}}
        @if (($certDays !== null && $certDays < 30) || ($firmaDays !== null && $firmaDays < 30))
            <div style="margin-top: 12px; background: #fef9c3; border: 1px solid #fde047; border-radius: 6px; padding: 10px 12px; font-size: 13px; color: #92400e;">
                ⚠️ <strong>Attenzione:</strong>
                @if ($certDays !== null && $certDays < 30)
                    Certificato mTLS
                    @if ($certDays < 0)
                        <strong>SCADUTO</strong>
                    @else
                        scade tra <strong>{{ $certDays }} giorni</strong>
                    @endif
                    ({{ $certScadenza?->format('d/m/Y') }}).
                @endif
                @if ($firmaDays !== null && $firmaDays < 30)
                    Certificato firma xFIR
                    @if ($firmaDays < 0)
                        <strong>SCADUTO</strong>
                    @else
                        scade tra <strong>{{ $firmaDays }} giorni</strong>
                    @endif
                    ({{ $firmaScadenza?->format('d/m/Y') }}).
                @endif
                Rinnovare tramite <a href="{{ route('segreteria.impostazioni.rentri') }}" style="color: #92400e; font-weight: 600;">Impostazioni RENTRI</a>.
            </div>
        @endif
    </div>
</div>
