<div>
    @include('livewire.partials.flash-messages')

    <div class="seg-page-header seg-page-header--actions mb-6">
        <div>
            <x-breadcrumb
                :items="['Fatturazione' => route('segreteria.fatture.index')]"
                :current="$fattura->numero_fattura"
            />
            <h1>{{ $fattura->numero_fattura }}</h1>
            <p>
                <span class="seg-badge"
                      style="background:{{ $fattura->statoColor() }}1a;color:{{ $fattura->statoColor() }};">
                    {{ $fattura->statoLabel() }}
                </span>
                <span class="seg-badge ml-1.5">{{ $fattura->tipoLabel() }}</span>
                @if ($fattura->sdi_stato)
                    <span class="seg-badge ml-1.5" style="background:{{ $fattura->sdiStatoColor() }}1a;color:{{ $fattura->sdiStatoColor() }};">
                        SDI: {{ $fattura->sdiStatoLabel() }}
                    </span>
                @endif
            </p>
        </div>
        <div class="seg-header-actions">
            @if ($fattura->stato === 'bozza')
                <a href="{{ route('segreteria.fatture.edit', $fattura) }}" class="seg-btn seg-btn-secondary" wire:navigate>
                    Modifica
                </a>
                <button wire:click="emetti" wire:confirm="Emettere questa fattura?"
                        wire:loading.attr="disabled" wire:target="emetti"
                        class="seg-btn seg-btn-primary">
                    <span wire:loading.remove wire:target="emetti">Emetti</span>
                    <span wire:loading wire:target="emetti">Emissione…</span>
                </button>
            @endif
            @if (in_array($fattura->stato, ['emessa', 'scaduta']))
                <button wire:click="$set('showPagamentoModal', true)" class="seg-btn seg-btn-primary">
                    Registra pagamento
                </button>
            @endif
            @php
                $destinatarioInvio = (config('pec.enabled') && filled($fattura->anagrafica?->pec))
                    ? $fattura->anagrafica->pec
                    : $fattura->anagrafica?->email;
                $canaleInvio = (config('pec.enabled') && filled($fattura->anagrafica?->pec)) ? 'PEC' : 'email';
            @endphp
            @if ($fattura->stato === 'emessa' && filled($destinatarioInvio))
                <button wire:click="inviaEmail" wire:confirm="Inviare la fattura a {{ $destinatarioInvio }} ({{ $canaleInvio }})?"
                        wire:loading.attr="disabled" wire:target="inviaEmail"
                        class="seg-btn seg-btn-secondary">
                    <span wire:loading.remove wire:target="inviaEmail">Invia per {{ strtolower($canaleInvio) }}</span>
                    <span wire:loading wire:target="inviaEmail">Invio…</span>
                </button>
            @endif
            @if (in_array($fattura->stato, ['emessa', 'pagata', 'scaduta']))
                @if ($fattura->fattura_pa_xml_path)
                    <button wire:click="downloadXmlFatturaPa" wire:loading.attr="disabled" wire:target="downloadXmlFatturaPa"
                            class="seg-btn seg-btn-secondary">
                        Scarica XML FatturaPA
                    </button>
                @else
                    <button wire:click="generaXmlFatturaPa" wire:loading.attr="disabled" wire:target="generaXmlFatturaPa"
                            class="seg-btn seg-btn-secondary">
                        <span wire:loading.remove wire:target="generaXmlFatturaPa">Genera XML FatturaPA</span>
                        <span wire:loading wire:target="generaXmlFatturaPa">Generazione…</span>
                    </button>
                @endif
                @if ($fattura->fattura_pa_xml_path && $fattura->sdi_stato !== \App\Enums\SdiStato::Inviata->value)
                    <button wire:click="inviaSdi" wire:confirm="{{ $sdiInvioConfirm }}"
                            wire:loading.attr="disabled" wire:target="inviaSdi"
                            class="seg-btn seg-btn-primary">
                        <span wire:loading.remove wire:target="inviaSdi">{{ $sdiInvioLabel }}</span>
                        <span wire:loading wire:target="inviaSdi">Accodamento…</span>
                    </button>
                @endif
            @endif
            @if ($fattura->stato !== 'annullata')
                <button wire:click="$set('showAnnullaModal', true)" class="seg-btn seg-btn-danger">
                    Annulla
                </button>
            @endif
            <button wire:click="downloadPdf" wire:loading.attr="disabled" wire:target="downloadPdf"
                    class="seg-btn seg-btn-secondary">
                PDF
            </button>
        </div>
    </div>

    <div class="grid grid-cols-[2fr_1fr] gap-4 mb-4">
        <div class="seg-card seg-card-padding">
            <h2 class="seg-section-title text-[15px] mb-4">Intestazione</h2>
            <dl class="grid grid-cols-2 gap-y-3 gap-x-6">
                <div>
                    <dt class="text-[11px] text-slate-400 uppercase tracking-wide">Cliente</dt>
                    <dd class="font-semibold mt-0.5">{{ $fattura->anagrafica?->ragione_sociale ?? '—' }}</dd>
                    @if ($fattura->anagrafica?->piva)
                        <dd class="text-xs text-slate-500">P.IVA {{ $fattura->anagrafica->piva }}</dd>
                    @endif
                </div>
                <div>
                    <dt class="text-[11px] text-slate-400 uppercase tracking-wide">Data emissione</dt>
                    <dd class="font-semibold mt-0.5">{{ $fattura->data_emissione?->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] text-slate-400 uppercase tracking-wide">Scadenza</dt>
                    <dd class="font-semibold mt-0.5">{{ $fattura->data_scadenza?->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] text-slate-400 uppercase tracking-wide">Metodo pagamento</dt>
                    <dd class="font-semibold mt-0.5">{{ $fattura->metodo_pagamento ?? '—' }}</dd>
                </div>
                @if ($fattura->data_pagamento)
                <div>
                    <dt class="text-[11px] text-slate-400 uppercase tracking-wide">Data pagamento</dt>
                    <dd class="font-semibold mt-0.5 text-green-600">{{ $fattura->data_pagamento->format('d/m/Y') }}</dd>
                </div>
                @endif
                @if ($fattura->vfu)
                <div>
                    <dt class="text-[11px] text-slate-400 uppercase tracking-wide">Veicolo VFU</dt>
                    <dd class="font-semibold mt-0.5">
                        <a href="{{ route('segreteria.vfu.show', $fattura->vfu) }}" wire:navigate class="text-blue-600">
                            {{ $fattura->vfu->targa }} – {{ $fattura->vfu->marca }} {{ $fattura->vfu->modello }}
                        </a>
                    </dd>
                </div>
                @endif
            </dl>

            @if ($fattura->note)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <dt class="text-[11px] text-slate-400 uppercase tracking-wide">Note</dt>
                    <dd class="mt-1 text-sm">{{ $fattura->note }}</dd>
                </div>
            @endif

            @if ($fattura->motivo_annullamento)
                <div class="mt-4 p-3 bg-red-50 rounded-lg border border-red-200">
                    <p class="m-0 text-sm text-red-600">
                        <strong>Motivo annullamento:</strong> {{ $fattura->motivo_annullamento }}
                    </p>
                </div>
            @endif
        </div>

        <div class="seg-card seg-card-padding flex flex-col gap-2.5">
            <h2 class="seg-section-title text-[15px] mb-2">Riepilogo</h2>
            <div class="flex justify-between text-sm">
                <span class="text-slate-500">Imponibile</span>
                <span class="font-semibold">€ {{ number_format((float) $fattura->imponibile, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-slate-500">IVA {{ $fattura->iva_percentuale }}%</span>
                <span class="font-semibold">€ {{ number_format((float) $fattura->iva_importo, 2, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-[17px] border-t-2 border-slate-900 pt-2.5 mt-1.5 font-bold">
                <span>Totale</span>
                <span>€ {{ number_format((float) $fattura->totale, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="seg-card seg-card-padding-none mb-4 overflow-hidden">
        <div class="px-4 py-4 border-b border-slate-100">
            <h2 class="seg-section-title text-[15px] m-0">Righe</h2>
        </div>
        <div class="seg-table-wrap">
            <table class="seg-table">
                <thead>
                    <tr>
                        <th class="w-[40%]">Descrizione</th>
                        <th class="text-right">Qtà</th>
                        <th class="text-right">Prezzo unit.</th>
                        <th class="text-right">IVA %</th>
                        <th class="text-right">Totale riga</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fattura->righe as $riga)
                        <tr wire:key="riga-{{ $riga->id }}">
                            <td>{{ $riga->descrizione }}</td>
                            <td class="text-right">{{ number_format((float) $riga->quantita, 2, ',', '.') }}</td>
                            <td class="text-right">€ {{ number_format((float) $riga->prezzo_unitario, 2, ',', '.') }}</td>
                            <td class="text-right">{{ $riga->iva_percentuale }}%</td>
                            <td class="text-right font-semibold">€ {{ number_format((float) $riga->totale_riga, 2, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="seg-table-empty py-6">Nessuna riga</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (in_array($fattura->stato, ['emessa', 'pagata', 'scaduta']))
        <div class="seg-card seg-card-padding mb-4 bg-slate-50 border border-slate-200">
            <p class="m-0 text-sm text-slate-500">
                <strong>FatturaPA / SDI:</strong>
                @if ($fattura->fattura_pa_xml_path)
                    XML generato e pronto per il download.
                @else
                    Genera l'XML conforme FatturaPA (FPA12/FPR12) per l'invio manuale o tramite intermediario.
                @endif
                @if ($fattura->sdi_stato === \App\Enums\SdiStato::Inviata->value)
                    Trasmessa a SDI{{ $sdiRuntime->isStub() ? ' (stub)' : '' }}.
                @elseif ($fattura->fattura_pa_xml_path)
                    Usa «{{ $sdiInvioLabel }}» per accodare la trasmissione.
                @else
                    Genera l'XML, poi invia a SDI.
                @endif
            </p>
        </div>
    @endif

    <livewire:timeline-widget :subject="$fattura" title="Storico fattura" wire:key="timeline-fattura-{{ $fattura->id }}" />

    @if ($showAnnullaModal)
        <div class="seg-modal-overlay" wire:keydown.escape="$set('showAnnullaModal', false)">
            <div class="seg-modal max-w-[440px]" role="dialog" aria-modal="true" aria-labelledby="annulla-modal-title">
                <div class="seg-modal-header">
                    <h2 id="annulla-modal-title" class="m-0 text-base">Annulla fattura</h2>
                    <button type="button" wire:click="$set('showAnnullaModal', false)" class="seg-modal-close" aria-label="Chiudi">&times;</button>
                </div>
                <div class="seg-modal-body">
                    <label class="seg-label">Motivo annullamento *</label>
                    <input wire:model="motivoAnnullamento" type="text" class="seg-input w-full mb-4"
                           placeholder="Specificare il motivo…">
                    @error('motivoAnnullamento')<p class="seg-error -mt-3 mb-3">{{ $message }}</p>@enderror
                    <div class="flex gap-2 justify-end">
                        <button wire:click="$set('showAnnullaModal', false)" class="seg-btn seg-btn-secondary">Annulla</button>
                        <button wire:click="confermaAnnulla" wire:loading.attr="disabled" wire:target="confermaAnnulla"
                                class="seg-btn seg-btn-danger">
                            <span wire:loading.remove wire:target="confermaAnnulla">Conferma</span>
                            <span wire:loading wire:target="confermaAnnulla">Annullamento…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showPagamentoModal)
        <div class="seg-modal-overlay" wire:keydown.escape="$set('showPagamentoModal', false)">
            <div class="seg-modal max-w-[380px]" role="dialog" aria-modal="true" aria-labelledby="pagamento-modal-title">
                <div class="seg-modal-header">
                    <h2 id="pagamento-modal-title" class="m-0 text-base">Registra pagamento</h2>
                    <button type="button" wire:click="$set('showPagamentoModal', false)" class="seg-modal-close" aria-label="Chiudi">&times;</button>
                </div>
                <div class="seg-modal-body">
                    <label class="seg-label">Data pagamento *</label>
                    <input wire:model="dataPagamento" type="date" class="seg-input w-full mb-4">
                    @error('dataPagamento')<p class="seg-error -mt-3 mb-3">{{ $message }}</p>@enderror
                    <div class="flex gap-2 justify-end">
                        <button wire:click="$set('showPagamentoModal', false)" class="seg-btn seg-btn-secondary">Annulla</button>
                        <button wire:click="confermaPagamento" wire:loading.attr="disabled" wire:target="confermaPagamento"
                                class="seg-btn seg-btn-primary">
                            <span wire:loading.remove wire:target="confermaPagamento">Registra</span>
                            <span wire:loading wire:target="confermaPagamento">Registrazione…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
