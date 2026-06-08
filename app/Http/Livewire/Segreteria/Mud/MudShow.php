<?php

namespace App\Http\Livewire\Segreteria\Mud;

use App\Domain\Mud\MudInvioTelematicoService;
use App\Domain\Mud\MudPdfExportService;
use App\Domain\Mud\MudService;
use App\Domain\Mud\MudXmlValidationService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\MudDichiarazione;
use App\Support\Logging\StructuredLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Dettaglio MUD')]
class MudShow extends SegreteriaPage
{
    use AuthorizesRequests;

    public MudDichiarazione $dichiarazione;

    public function mount(MudDichiarazione $dichiarazione): void
    {
        $this->authorize('view', $dichiarazione);
        $this->dichiarazione = $dichiarazione->load('user');
    }

    public function completa(MudService $mud): void
    {
        $this->authorize('complete', $this->dichiarazione);

        try {
            $this->dichiarazione = $mud->completa($this->dichiarazione);
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('success', 'Dichiarazione MUD completata. Export JSON, XML e PDF disponibili.');
    }

    public function inviaStub(MudInvioTelematicoService $invio): void
    {
        $this->inviaTelematico($invio);
    }

    public function inviaTelematico(MudInvioTelematicoService $invio): void
    {
        $this->authorize('invioTelematico', $this->dichiarazione);

        try {
            $this->dichiarazione = $invio->invia($this->dichiarazione, (int) auth()->id());
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

            return;
        } catch (ValidationException $e) {
            session()->flash('error', $e->validator->errors()->first());

            return;
        }

        $runtime = app(\App\Domain\Mud\MudTelematicoRuntimeModeService::class);
        $mode = $runtime->isStub() ? 'stub' : 'live';

        session()->flash('success', sprintf(
            'Invio telematico %s completato. Protocollo: %s',
            $mode,
            $this->dichiarazione->invio_protocollo,
        ));
    }

    public function exportJson(MudService $mud, StructuredLogService $logger): StreamedResponse
    {
        $this->authorize('export', $this->dichiarazione);

        $payload = $this->dichiarazione->export_payload
            ?? $mud->buildExportPayload($this->dichiarazione);

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $filename = $mud->exportFilename($this->dichiarazione);

        $logger->info('operatore', 'mud.export.json', 'Export JSON MUD generato', [
            'entity_type' => 'MudDichiarazione',
            'entity_id'   => $this->dichiarazione->id,
            'user_id'     => auth()->id(),
            'extra'       => [
                'anno_riferimento' => $this->dichiarazione->anno_riferimento,
                'filename'         => $filename,
                'simulazione'      => (bool) ($payload['simulazione'] ?? false),
            ],
        ]);

        return response()->streamDownload(
            fn () => print($json),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }

    public function exportXml(MudService $mud, MudXmlValidationService $xml, StructuredLogService $logger): StreamedResponse
    {
        $this->authorize('export', $this->dichiarazione);

        $content = $xml->buildXml($this->dichiarazione, $mud);
        $filename = sprintf('mud-%d.xml', $this->dichiarazione->anno_riferimento);

        $logger->info('operatore', 'mud.export.xml', 'Export XML MUD generato', [
            'entity_type' => 'MudDichiarazione',
            'entity_id'   => $this->dichiarazione->id,
            'user_id'     => auth()->id(),
            'extra'       => [
                'anno_riferimento' => $this->dichiarazione->anno_riferimento,
                'filename'         => $filename,
                'simulazione'      => (bool) config('services.mud_telematico.stub', true),
            ],
        ]);

        return response()->streamDownload(
            fn () => print($content),
            $filename,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }

    public function exportPdf(MudService $mud, MudPdfExportService $pdf, StructuredLogService $logger): StreamedResponse
    {
        $this->authorize('export', $this->dichiarazione);

        $logger->info('operatore', 'mud.export.pdf', 'Export PDF MUD generato', [
            'entity_type' => 'MudDichiarazione',
            'entity_id'   => $this->dichiarazione->id,
            'user_id'     => auth()->id(),
            'extra'       => [
                'anno_riferimento' => $this->dichiarazione->anno_riferimento,
                'filename'         => $pdf->filename($this->dichiarazione),
            ],
        ]);

        return $pdf->downloadResponse($this->dichiarazione, $mud);
    }

    public function render(MudService $mud, MudInvioTelematicoService $invio): View
    {
        $runtime = app(\App\Domain\Mud\MudTelematicoRuntimeModeService::class);
        $endpoints = app(\App\Domain\Mud\MudTelematicoEndpoints::class);

        return $this->segreteriaView(
            'livewire.segreteria.mud.show',
            [
                'service'              => $mud,
                'preInvioChecklist'    => $invio->preInvioChecklist($this->dichiarazione),
                'canInviare'           => $invio->canInviare($this->dichiarazione),
                'telematicoRuntime'    => $runtime,
                'mudEndpoints'         => $endpoints,
                'endpointProbe'        => $runtime->isStub() ? null : $endpoints->probeReachability(),
                'invioButtonLabel'     => $runtime->invioButtonLabel(),
                'invioConfirmMessage'  => $runtime->invioConfirmMessage(),
            ],
            'mud',
            'MUD '.$this->dichiarazione->anno_riferimento,
        );
    }
}
