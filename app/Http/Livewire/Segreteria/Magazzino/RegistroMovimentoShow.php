<?php

namespace App\Http\Livewire\Segreteria\Magazzino;

use App\Enums\VfuTipoDocumento;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\BonificaVfuMovimento;
use App\Models\MagazzinoCaricoManuale;
use App\Models\RegistroMovimento;
use App\Models\Trasporto;
use App\Models\VfuRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Dettaglio movimento registro')]
class RegistroMovimentoShow extends SegreteriaPage
{
    use AuthorizesRequests;

    public RegistroMovimento $movimento;

    public function mount(RegistroMovimento $movimento): void
    {
        $this->authorize('view', $movimento);
        $this->movimento = $movimento->load([
            'codiceCer',
            'rentriTransmissione',
            'source',
        ]);
    }

    /**
     * @return array{label: string, href: string|null}
     */
    public function sourceInfo(): array
    {
        $source = $this->movimento->source;

        return match ($this->movimento->source_type) {
            RegistroMovimento::SOURCE_VFU_REGISTRATION => $source instanceof VfuRegistration
                ? [
                    'label' => sprintf('VFU #%d — %s', $source->id, $source->targa),
                    'href'  => route('segreteria.vfu.show', $source),
                ]
                : ['label' => 'Accettazione VFU', 'href' => null],
            RegistroMovimento::SOURCE_TRASPORTO => $source instanceof Trasporto
                ? [
                    'label' => sprintf('Trasporto #%d', $source->id),
                    'href'  => route('segreteria.trasporti.show', $source),
                ]
                : ['label' => 'Trasporto', 'href' => null],
            RegistroMovimento::SOURCE_BONIFICA_MOVIMENTO => $source instanceof BonificaVfuMovimento
                ? [
                    'label' => sprintf(
                        'Bonifica VFU #%d',
                        $source->bonifica?->vfu_registration_id ?? $source->bonifica_vfu_id,
                    ),
                    'href'  => $source->bonifica?->vfu_registration_id
                        ? route('segreteria.vfu.show', $source->bonifica->vfu_registration_id)
                        : null,
                ]
                : ['label' => 'Bonifica VFU', 'href' => null],
            RegistroMovimento::SOURCE_CARICO_MANUALE => [
                'label' => 'Carico manuale',
                'href'  => route('segreteria.magazzino.show', $this->movimento->codice_cer_id),
            ],
            default => [
                'label' => $this->movimento->source_type
                    ? class_basename($this->movimento->source_type)
                    : '—',
                'href'  => null,
            ],
        };
    }

    /**
     * @return list<array{label: string, href: string|null, meta: string|null}>
     */
    public function linkedDocuments(): array
    {
        $docs = [];
        $source = $this->movimento->source;

        if ($source instanceof Trasporto) {
            $source->loadMissing(['firCollegato', 'fir']);
            $fir = $source->firCollegato ?? $source->fir;

            if ($fir) {
                $docs[] = [
                    'label' => 'FIR '.$fir->numero_fir,
                    'href'  => route('segreteria.trasporti.show', $source),
                    'meta'  => $fir->stato?->value,
                ];
            }
        }

        if ($source instanceof VfuRegistration) {
            $source->loadMissing('documents');
            foreach ($source->documents as $document) {
                if (in_array($document->tipo, [
                    VfuTipoDocumento::CertificatoRottamazioneProvvisorio,
                    VfuTipoDocumento::CertificatoRottamazioneDefinitivo,
                ], true)) {
                    $docs[] = [
                        'label' => $document->tipo->label(),
                        'href'  => route('segreteria.vfu.show', $source),
                        'meta'  => $document->original_name,
                    ];
                }
            }
        }

        if ($source instanceof BonificaVfuMovimento) {
            $source->loadMissing('bonifica.vfuRegistration.documents');
            $vfu = $source->bonifica?->vfuRegistration;

            if ($vfu) {
                foreach ($vfu->documents as $document) {
                    if ($document->tipo === VfuTipoDocumento::CertificatoRottamazioneDefinitivo) {
                        $docs[] = [
                            'label' => $document->tipo->label(),
                            'href'  => route('segreteria.vfu.show', $vfu),
                            'meta'  => $document->original_name,
                        ];
                    }
                }
            }
        }

        return $docs;
    }

    public function operatoreLabel(): string
    {
        $source = $this->movimento->source;

        if ($source instanceof MagazzinoCaricoManuale) {
            $source->loadMissing('user');

            return $source->user?->name ?? '—';
        }

        return '—';
    }

    public function render(): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.magazzino.registro-movimento-show',
            [
                'sourceInfo'      => $this->sourceInfo(),
                'linkedDocuments' => $this->linkedDocuments(),
                'operatoreLabel'  => $this->operatoreLabel(),
            ],
            'registro-movimenti',
            'Movimento #'.$this->movimento->id,
            'Magazzino / Registro movimenti',
        );
    }
}
