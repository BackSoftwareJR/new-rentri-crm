<?php

namespace App\Domain\Vfu;

use App\Enums\VfuStato;
use App\Models\VfuRegistration;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificatoRottamazioneGeneratorService
{
    /** @return list<VfuStato> */
    public function eligibleStates(): array
    {
        return [
            VfuStato::Bonificato,
            VfuStato::Rottamato,
            VfuStato::InviatoAgenzia,
        ];
    }

    public function isEligible(VfuRegistration $vfu): bool
    {
        return in_array($vfu->stato, $this->eligibleStates(), true);
    }

    public function assertEligible(VfuRegistration $vfu): void
    {
        if (! $this->isEligible($vfu)) {
            throw ValidationException::withMessages([
                'certificato' => 'Certificato disponibile solo per VFU bonificati, rottamati o inviati ad agenzia.',
            ]);
        }
    }

    public function renderHtml(VfuRegistration $vfu): string
    {
        $this->assertEligible($vfu);

        return view('pdf.certificato-rottamazione', [
            'vfu' => $vfu,
        ])->render();
    }

    public function generatePdf(VfuRegistration $vfu): string
    {
        $this->assertEligible($vfu);

        return $this->buildMinimalPdf($vfu);
    }

    public function downloadResponse(VfuRegistration $vfu): StreamedResponse
    {
        $pdf = $this->generatePdf($vfu);
        $filename = sprintf('certificato-rottamazione-%s.pdf', $vfu->targa);

        return response()->streamDownload(
            static fn () => print($pdf),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function filename(VfuRegistration $vfu): string
    {
        return sprintf('certificato-rottamazione-%s.pdf', $vfu->targa);
    }

    private function buildMinimalPdf(VfuRegistration $vfu): string
    {
        $lines = [
            'Certificato di rottamazione (stub MVP — non ufficiale)',
            'Targa: '.$vfu->targa,
            'Telaio: '.$vfu->telaio,
            'Marca/Modello: '.$vfu->marca.' '.$vfu->modello,
            'Proprietario: '.($vfu->proprietario ?: '-'),
            'Stato pratica: '.$vfu->stato->label(),
            'Data emissione: '.now()->format('d/m/Y'),
        ];

        $stream = "BT\n/F1 12 Tf\n50 800 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $stream .= "0 -18 Td\n";
            }
            $stream .= '('.$this->escapePdfString($this->toPdfSafe($line)).") Tj\n";
        }
        $stream .= "ET\n";

        $streamLength = strlen($stream);

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n",
            "4 0 obj\n<< /Length {$streamLength} >>\nstream\n{$stream}endstream\nendobj\n",
            "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".count($offsets)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < count($offsets); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size ".count($offsets)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function toPdfSafe(string $text): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return $ascii !== false ? $ascii : $text;
    }

    private function escapePdfString(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
