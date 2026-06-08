<?php

namespace App\Domain\Fir;

use App\Models\Anagrafica;
use App\Models\Authorization;
use App\Models\CompanySetting;
use App\Models\Fir;
use App\Models\RentriSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FirPdfGeneratorService
{
    public function __construct(
        private FirService $firService,
    ) {}

    public function assertEligible(Fir $fir): void
    {
        if ($fir->vidimato_at === null) {
            throw new \InvalidArgumentException('Il PDF FIR è disponibile solo per formulari vidimati.');
        }
    }

    public function generate(Fir $fir): string
    {
        $this->assertEligible($fir);

        $path = $this->storagePath($fir);
        Storage::disk('local')->put($path, $this->buildPdf($fir));

        return $path;
    }

    public function download(Fir $fir): StreamedResponse
    {
        $path = $this->generate($fir);
        $content = Storage::disk('local')->get($path);

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            $this->filename($fir),
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function filename(Fir $fir): string
    {
        $slug = Str::slug($this->firService->numeroDisplay($fir), '-');

        return sprintf('fir-%s.pdf', $slug !== '' ? $slug : (string) $fir->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(Fir $fir): array
    {
        $this->assertEligible($fir);

        $fir->loadMissing([
            'trasporto.codiceCer',
            'trasporto.destinatario.authorizations',
            'trasporto.svuotamento.trasportatore.authorizations',
            'blocco',
        ]);

        $settings = RentriSetting::instance();
        $trasporto = $fir->trasporto;
        $trasportatore = $trasporto?->svuotamento?->trasportatore;
        $destinatario = $trasporto?->destinatario;
        $cer = $trasporto?->codiceCer;

        /** @var array<string, mixed> $qr */
        $qr = json_decode($fir->qr_payload ?? '{}', true) ?: [];

        $produttoreNome = $settings->ragione_sociale
            ?: CompanySetting::get('company_ragione_sociale')
            ?: config('app.name', 'Autodemolitore');

        $produttoreAlbo = $settings->num_iscr_sito
            ?: CompanySetting::get('company_num_albo')
            ?: '—';

        $produttoreIndirizzo = collect([
            CompanySetting::get('company_indirizzo'),
            CompanySetting::get('company_cap'),
            CompanySetting::get('company_citta'),
            CompanySetting::get('company_provincia') ? '('.CompanySetting::get('company_provincia').')' : null,
        ])->filter()->implode(' ');

        return [
            'fir'                  => $fir,
            'numeroFir'            => $this->firService->numeroDisplay($fir),
            'dataFir'              => $fir->vidimato_at?->format('d/m/Y') ?? now()->format('d/m/Y'),
            'progressivo'          => $fir->progressivo,
            'produttoreNome'       => $produttoreNome,
            'produttoreAlbo'       => $produttoreAlbo,
            'produttoreCf'         => strtoupper($settings->cf ?: CompanySetting::get('company_cf') ?: '—'),
            'produttoreIndirizzo'  => $produttoreIndirizzo !== '' ? $produttoreIndirizzo : '—',
            'trasportatoreNome'    => $trasporto?->svuotamento?->trasportatore_omesso
                ? 'Non indicato'
                : ($trasportatore?->ragione_sociale ?? '—'),
            'trasportatoreAlbo'    => $this->iscrizioneAlbo($trasportatore),
            'targaVeicolo'         => '—',
            'destinatarioNome'     => $destinatario?->ragione_sociale ?? '—',
            'destinatarioIndirizzo' => $this->indirizzoAnagrafica($destinatario),
            'destinatarioAlbo'     => $this->iscrizioneAlbo($destinatario),
            'cerCodice'            => $cer?->codice ?? '—',
            'cerDescrizione'       => $cer?->descrizione ?? '—',
            'statoFisico'          => $this->statoFisicoLabel($cer?->categoria),
            'quantitaKg'           => number_format((float) ($fir->peso_partenza_kg ?? $trasporto?->quantita_kg ?? 0), 2, ',', '.'),
            'dataInizioTrasporto'  => $fir->vidimato_at?->format('d/m/Y H:i') ?? '—',
            'vidimazioneNumero'    => (string) ($qr['protocollo'] ?? $fir->numero_fir ?? '—'),
            'vidimazioneData'      => isset($qr['data_vidimazione'])
                ? \Illuminate\Support\Carbon::parse($qr['data_vidimazione'])->format('d/m/Y')
                : ($fir->vidimato_at?->format('d/m/Y') ?? '—'),
            'qrPayload'            => $qr['qr_code'] ?? null,
            'numIscrSito'          => (string) ($qr['num_iscr_sito'] ?? $settings->num_iscr_sito ?? '—'),
        ];
    }

    private function buildPdf(Fir $fir): string
    {
        return Pdf::loadView('pdf.fir', $this->viewData($fir))
            ->setPaper('a4', 'portrait')
            ->output();
    }

    private function storagePath(Fir $fir): string
    {
        $year = $fir->vidimato_at?->format('Y') ?? now()->format('Y');
        $filename = $this->filename($fir);

        return 'fir/'.$year.'/'.$filename;
    }

    private function iscrizioneAlbo(?Anagrafica $anagrafica): string
    {
        if ($anagrafica === null) {
            return '—';
        }

        if (filled($anagrafica->rentri_iscrizione_numero)) {
            return (string) $anagrafica->rentri_iscrizione_numero;
        }

        /** @var Collection<int, Authorization> $authorizations */
        $authorizations = $anagrafica->relationLoaded('authorizations')
            ? $anagrafica->authorizations
            : $anagrafica->authorizations()->get();

        $auth = $authorizations->sortByDesc(fn (Authorization $a) => $a->scade_il?->timestamp ?? 0)->first();

        return $auth?->numero ?? '—';
    }

    private function indirizzoAnagrafica(?Anagrafica $anagrafica): string
    {
        if ($anagrafica === null) {
            return '—';
        }

        $parts = array_filter([
            $anagrafica->indirizzo,
            $anagrafica->cap,
            $anagrafica->citta,
            $anagrafica->provincia ? '('.$anagrafica->provincia.')' : null,
        ]);

        return $parts !== [] ? implode(' ', $parts) : '—';
    }

    private function statoFisicoLabel(?string $categoria): string
    {
        if ($categoria === null || trim($categoria) === '') {
            return 'SF — Solido frammentato';
        }

        return match (strtolower(trim($categoria))) {
            'liquido', 'l' => 'L — Liquido',
            'fangoso', 'f' => 'F — Fangoso',
            'solido', 's' => 'S — Solido',
            'solido frammentato', 'sf' => 'SF — Solido frammentato',
            'polverulento', 'p' => 'P — Polverulento',
            'vischioso', 'v' => 'V — Viscoso',
            default => strtoupper($categoria),
        };
    }
}
