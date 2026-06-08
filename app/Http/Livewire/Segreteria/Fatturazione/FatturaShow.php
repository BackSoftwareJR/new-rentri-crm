<?php

namespace App\Http\Livewire\Segreteria\Fatturazione;

use App\Domain\Fatturazione\FatturaPaXmlGeneratorService;
use App\Domain\Fatturazione\FatturazioneService;
use App\Domain\Fatturazione\SdiRuntimeModeService;
use App\Enums\SdiStato;
use App\Jobs\TransmitFatturaSdiJob;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Mail\FatturaEmailMail;
use App\Models\Fattura;
use App\Services\Pec\PecMailService;
use App\Support\Logging\StructuredLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Fattura')]
class FatturaShow extends SegreteriaPage
{
    use AuthorizesRequests;

    public Fattura $fattura;

    public bool $showAnnullaModal = false;
    public string $motivoAnnullamento = '';

    public bool $showPagamentoModal = false;
    public string $dataPagamento = '';

    public function mount(Fattura $fattura): void
    {
        $this->authorize('view', $fattura);
        $this->fattura = $fattura->load(['anagrafica', 'righe', 'vfu']);
        $this->dataPagamento = now()->toDateString();
    }

    public function emetti(FatturazioneService $service): void
    {
        $this->authorize('update', $this->fattura);
        $service->emettiFattura($this->fattura);
        $this->fattura->refresh()->load(['anagrafica', 'righe', 'vfu']);
        session()->flash('success', "Fattura {$this->fattura->numero_fattura} emessa.");
    }

    public function confermaPagamento(FatturazioneService $service): void
    {
        $this->authorize('manage', $this->fattura);
        $this->validate(['dataPagamento' => 'required|date']);
        $service->registraPagamento($this->fattura, Carbon::parse($this->dataPagamento));
        $this->fattura->refresh();
        $this->showPagamentoModal = false;
        session()->flash('success', 'Pagamento registrato.');
    }

    public function confermaAnnulla(FatturazioneService $service): void
    {
        $this->authorize('delete', $this->fattura);
        $this->validate(['motivoAnnullamento' => 'required|min:5']);
        $service->annulla($this->fattura, $this->motivoAnnullamento);
        $this->fattura->refresh();
        $this->showAnnullaModal = false;
        session()->flash('success', "Fattura annullata.");
    }

    public function inviaEmail(FatturazioneService $service, PecMailService $pecMail, StructuredLogService $log): void
    {
        $this->authorize('manage', $this->fattura);

        if ($this->fattura->stato !== 'emessa') {
            session()->flash('error', 'La fattura può essere inviata solo se in stato emessa.');

            return;
        }

        $anagrafica = $this->fattura->anagrafica;
        $usePec     = $pecMail->isEnabled() && filled($anagrafica?->pec);
        $recipient  = $usePec ? $anagrafica->pec : $anagrafica?->email;

        if (blank($recipient)) {
            session()->flash('error', 'Il cliente non ha un indirizzo email o PEC configurato.');

            return;
        }

        if (! $this->fattura->pdf_path) {
            $path = $service->generaPdf($this->fattura);
            $this->fattura->update(['pdf_path' => $path]);
            $this->fattura->refresh();
        }

        $fattura = $this->fattura->loadMissing(['anagrafica', 'righe']);

        if ($usePec) {
            $body = view('mail.fattura-email', ['fattura' => $fattura])->render();
            $sent = $pecMail->send(
                $recipient,
                'Fattura '.$fattura->numero_fattura,
                $body,
                [[
                    'path' => Storage::disk('local')->path($fattura->pdf_path),
                    'name' => basename($fattura->pdf_path),
                    'mime' => 'application/pdf',
                ]],
            );

            if (! $sent) {
                session()->flash('error', 'Invio PEC fallito. Verificare la configurazione PEC.');

                return;
            }
        } else {
            Mail::to($recipient)->queue(new FatturaEmailMail($fattura, $fattura->pdf_path));
        }

        $log->info('business', 'fattura.email_inviata', 'Fattura inviata al cliente', [
            'entity_type'  => 'fattura',
            'entity_id'    => $this->fattura->id,
            'numero'       => $this->fattura->numero_fattura,
            'destinatario' => $recipient,
            'canale'       => $usePec ? 'pec' : 'email',
        ]);

        $canale = $usePec ? ' (PEC)' : '';
        session()->flash('success', "Fattura inviata a {$recipient}{$canale}.");
    }

    public function generaXmlFatturaPa(FatturaPaXmlGeneratorService $generator): void
    {
        $this->authorize('manage', $this->fattura);

        if (! in_array($this->fattura->stato, ['emessa', 'pagata', 'scaduta'], true)) {
            session()->flash('error', 'XML FatturaPA generabile solo per fatture emesse.');

            return;
        }

        try {
            $generator->generate($this->fattura);
            $this->fattura->refresh()->load(['anagrafica', 'righe', 'vfu']);
            session()->flash('success', 'XML FatturaPA generato. Puoi inviare a SDI dal pulsante dedicato.');
        } catch (\LogicException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function downloadXmlFatturaPa(FatturaPaXmlGeneratorService $generator): StreamedResponse
    {
        $this->authorize('view', $this->fattura);

        if (! $this->fattura->fattura_pa_xml_path) {
            $generator->generate($this->fattura);
            $this->fattura->refresh();
        }

        $path     = $this->fattura->fattura_pa_xml_path;
        $filename = basename($path);
        $content  = Storage::disk('local')->get($path);

        return response()->streamDownload(
            static function () use ($content) { echo $content; },
            $filename,
            ['Content-Type' => 'application/xml'],
        );
    }

    public function inviaSdi(SdiRuntimeModeService $sdiRuntime): void
    {
        $this->authorize('manage', $this->fattura);

        if (! in_array($this->fattura->stato, ['emessa', 'pagata', 'scaduta'], true)) {
            session()->flash('error', 'Trasmissione SDI consentita solo per fatture emesse.');

            return;
        }

        if ($this->fattura->sdi_stato === SdiStato::Inviata->value) {
            session()->flash('error', 'Fattura già trasmessa a SDI.');

            return;
        }

        TransmitFatturaSdiJob::dispatch($this->fattura->id);

        $mode = $sdiRuntime->isStub() ? ' (stub)' : '';
        session()->flash('success', "Trasmissione SDI accodata{$mode}. Aggiorna la pagina tra qualche secondo.");
    }

    public function downloadPdf(FatturazioneService $service): StreamedResponse
    {
        $this->authorize('view', $this->fattura);

        if (! $this->fattura->pdf_path) {
            $path = $service->generaPdf($this->fattura);
            $this->fattura->update(['pdf_path' => $path]);
            $this->fattura->refresh();
        }

        $path     = $this->fattura->pdf_path;
        $filename = basename($path);
        $content  = \Illuminate\Support\Facades\Storage::disk('local')->get($path);

        return response()->streamDownload(
            static function () use ($content) { echo $content; },
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function render(): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.fatturazione.fattura-show',
            [
                'fattura'            => $this->fattura,
                'sdiRuntime'         => app(SdiRuntimeModeService::class),
                'sdiInvioLabel'      => app(SdiRuntimeModeService::class)->invioButtonLabel(),
                'sdiInvioConfirm'    => app(SdiRuntimeModeService::class)->invioConfirmMessage(),
            ],
            'fatturazione',
            $this->fattura->numero_fattura,
            'Fatturazione',
        );
    }
}
