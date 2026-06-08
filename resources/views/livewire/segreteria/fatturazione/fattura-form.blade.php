<div>
    <div class="seg-page-header mb-6">
        <x-breadcrumb
            :items="['Fatturazione' => route('segreteria.fatture.index')]"
            :current="$fatturaId ? 'Modifica Fattura' : 'Nuova Fattura'"
        />
        <h1>{{ $fatturaId ? 'Modifica Fattura' : 'Nuova Fattura' }}</h1>
    </div>

    <form wire:submit="save">

        <div class="seg-card seg-card-padding mb-4">
            <h2 class="seg-section-title text-[15px] mb-4">Intestazione</h2>
            <div class="grid gap-4 grid-cols-[repeat(auto-fit,minmax(200px,1fr))]">

                <div>
                    <label class="seg-label">Tipo *</label>
                    <select wire:model="tipo" class="seg-input w-full">
                        <option value="fattura">Fattura</option>
                        <option value="nota_credito">Nota di credito</option>
                        <option value="preventivo">Preventivo</option>
                    </select>
                    @error('tipo')<p class="seg-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="seg-label">Cliente *</label>
                    <input wire:model.live.debounce.300ms="anagraficaSearch" type="search"
                           class="seg-input w-full mb-1.5"
                           placeholder="Cerca per ragione sociale o P.IVA…"
                           aria-label="Cerca cliente">
                    <select wire:model="anagraficaId" class="seg-input w-full">
                        <option value="">— Seleziona —</option>
                        @foreach ($this->anagrafiche as $ana)
                            <option value="{{ $ana->id }}">{{ $ana->ragione_sociale }}
                                @if($ana->piva) ({{ $ana->piva }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('anagraficaId')<p class="seg-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="seg-label">Data emissione *</label>
                    <input wire:model="dataEmissione" type="date" class="seg-input w-full">
                    @error('dataEmissione')<p class="seg-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="seg-label">Data scadenza</label>
                    <input wire:model="dataScadenza" type="date" class="seg-input w-full">
                    @error('dataScadenza')<p class="seg-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="seg-label">IVA %</label>
                    <select wire:model.live="ivaPercentuale" class="seg-input w-full">
                        <option value="0">0% (esente)</option>
                        <option value="4">4%</option>
                        <option value="10">10%</option>
                        <option value="22">22%</option>
                    </select>
                </div>

                <div>
                    <label class="seg-label">Metodo pagamento</label>
                    <select wire:model="metodoPagamento" class="seg-input w-full">
                        <option value="">— Seleziona —</option>
                        <option value="Bonifico">Bonifico bancario</option>
                        <option value="Contanti">Contanti</option>
                        <option value="POS">POS / Carta</option>
                        <option value="Assegno">Assegno</option>
                        <option value="RiBa">RiBa</option>
                    </select>
                </div>

                <div>
                    <label class="seg-label">Veicolo VFU (opzionale)</label>
                    <input wire:model.live.debounce.300ms="vfuSearch" type="search"
                           class="seg-input w-full mb-1.5"
                           placeholder="Cerca per targa, telaio, marca…"
                           aria-label="Cerca veicolo VFU">
                    <select wire:model="riferimentoVfuId" class="seg-input w-full">
                        <option value="">— Nessuno —</option>
                        @foreach ($this->vfuList as $vfu)
                            <option value="{{ $vfu->id }}">{{ $vfu->targa ?: $vfu->telaio }} – {{ $vfu->marca }} {{ $vfu->modello }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            <div class="mt-4">
                <label class="seg-label">Note</label>
                <textarea wire:model="note" class="seg-input w-full resize-y" rows="3"
                          placeholder="Note aggiuntive…"></textarea>
            </div>
        </div>

        <div class="seg-card seg-card-padding-none mb-4 overflow-hidden">
            <div class="flex justify-between items-center px-4 py-3 border-b border-slate-100">
                <h2 class="seg-section-title text-[15px] m-0">Righe</h2>
                <button type="button" wire:click="addRiga" class="seg-btn seg-btn-sm seg-btn-secondary">+ Aggiungi riga</button>
            </div>
            <div class="seg-table-wrap">
                <table class="seg-table">
                    <thead>
                        <tr>
                            <th class="w-[40%]">Descrizione *</th>
                            <th class="w-20 text-right">Qtà</th>
                            <th class="w-[110px] text-right">Prezzo unit.</th>
                            <th class="w-[70px] text-right">IVA %</th>
                            <th class="w-[100px] text-right">Totale riga</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($righe as $i => $riga)
                            <tr wire:key="riga-{{ $i }}">
                                <td>
                                    <input wire:model.live="righe.{{ $i }}.descrizione" type="text"
                                           class="seg-input w-full" placeholder="Descrizione servizio/prodotto">
                                    @error("righe.{$i}.descrizione")<p class="seg-error">{{ $message }}</p>@enderror
                                </td>
                                <td>
                                    <input wire:model.live="righe.{{ $i }}.quantita" type="number"
                                           step="0.001" min="0.001" class="seg-input w-[72px] text-right">
                                </td>
                                <td>
                                    <input wire:model.live="righe.{{ $i }}.prezzo_unitario" type="number"
                                           step="0.01" min="0" class="seg-input w-[104px] text-right">
                                </td>
                                <td>
                                    <input wire:model.live="righe.{{ $i }}.iva_percentuale" type="number"
                                           step="1" min="0" max="100" class="seg-input w-16 text-right">
                                </td>
                                <td class="text-right font-semibold text-[13px] whitespace-nowrap">
                                    € {{ number_format(round((float)($riga['quantita'] ?? 1) * (float)($riga['prezzo_unitario'] ?? 0), 2), 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    @if (count($righe) > 1)
                                        <button type="button" wire:click="removeRiga({{ $i }})"
                                                class="text-red-600 bg-transparent border-0 cursor-pointer text-base leading-none"
                                                aria-label="Elimina riga">×</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @error('righe')<p class="seg-error px-4 py-2">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end mb-6">
            <div class="seg-card seg-card-padding min-w-[260px]">
                <div class="flex justify-between text-sm mb-1.5">
                    <span class="text-slate-500">Imponibile</span>
                    <span class="font-semibold">€ {{ number_format($imponibile, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-sm mb-1.5">
                    <span class="text-slate-500">IVA {{ $ivaPercentuale }}%</span>
                    <span class="font-semibold">€ {{ number_format($ivaImporto, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-[17px] border-t-2 border-slate-900 pt-2.5 mt-1 font-bold">
                    <span>Totale</span>
                    <span>€ {{ number_format($totale, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="flex gap-2 justify-end">
            <a href="{{ route('segreteria.fatture.index') }}" wire:navigate class="seg-btn seg-btn-secondary">Annulla</a>
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                    class="seg-btn seg-btn-primary">
                <span wire:loading.remove wire:target="save">{{ $fatturaId ? 'Salva modifiche' : 'Crea fattura' }}</span>
                <span wire:loading wire:target="save">Salvataggio…</span>
            </button>
        </div>

    </form>
</div>
