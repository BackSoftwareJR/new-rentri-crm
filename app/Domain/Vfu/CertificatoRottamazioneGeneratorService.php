<?php

namespace App\Domain\Vfu;

use App\Enums\VfuStato;
use App\Models\RentriSetting;
use App\Models\VfuRegistration;
use App\Support\Demo\DemoContext;
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

    public function numeroCertificato(VfuRegistration $vfu): string
    {
        return sprintf('CERT-%s-%05d', now()->format('Y'), $vfu->id);
    }

    public function isBozza(): bool
    {
        return config('app.env') !== 'production' || DemoContext::isActive();
    }

    public function renderHtml(VfuRegistration $vfu): string
    {
        $this->assertEligible($vfu);

        return view('pdf.certificato-rottamazione', [
            'vfu'              => $vfu,
            'settings'         => RentriSetting::instance(),
            'numeroCertificato' => $this->numeroCertificato($vfu),
            'isBozza'          => $this->isBozza(),
        ])->render();
    }

    public function generatePdf(VfuRegistration $vfu): string
    {
        $this->assertEligible($vfu);

        return $this->buildPdf($vfu);
    }

    public function downloadResponse(VfuRegistration $vfu): StreamedResponse
    {
        $pdf = $this->generatePdf($vfu);
        $filename = $this->filename($vfu);

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

    private function buildPdf(VfuRegistration $vfu): string
    {
        $settings = RentriSetting::instance();
        $isBozza  = $this->isBozza();
        $numCert  = $this->numeroCertificato($vfu);

        // A4 page: 595 x 842 pt, margins: left=50, right=545
        $left  = 50;
        $right = 545;
        $pageW = 595;

        $stream = '';

        // BOZZA diagonal watermark — painted first so main text renders on top
        if ($isBozza) {
            $stream .= "q\n";
            $stream .= "0.87 g\n";                       // light grey fill
            $stream .= "BT\n";
            $stream .= "/F1 72 Tf\n";
            // 45° counterclockwise rotation anchored near page centre
            $stream .= "0.707 0.707 -0.707 0.707 130 310 Tm\n";
            $stream .= '('.$this->pdfStr('BOZZA').") Tj\n";
            $stream .= "ET\n";
            $stream .= "Q\n";
        }

        // Switch to black for main content
        $stream .= "0 g\n";

        // --- Header ---
        $y = 800;
        $stream .= $this->hRule($left, $right, $y + 12);

        $stream .= "BT\n";
        $stream .= "/F2 14 Tf\n";
        $stream .= "{$left} {$y} Td\n";
        $stream .= '('.$this->pdfStr('Certificato di rottamazione').") Tj\n";
        $stream .= "ET\n";

        $y -= 16;
        $stream .= "BT\n";
        $stream .= "/F1 9 Tf\n";
        $stream .= "{$left} {$y} Td\n";
        $stream .= '('.$this->pdfStr('Ai sensi del D.Lgs. 24 giugno 2003, n. 209 e s.m.i.').") Tj\n";
        $stream .= "ET\n";

        // Certificate number — right-aligned area
        $stream .= "BT\n";
        $stream .= "/F2 9 Tf\n";
        $stream .= "400 {$y} Td\n";
        $stream .= '('.$this->pdfStr('N. '.$numCert).") Tj\n";
        $stream .= "ET\n";

        $y -= 6;
        $stream .= $this->hRule($left, $right, $y);

        // --- Section 1: Dati del detentore ---
        $y -= 14;
        $stream .= $this->sectionTitle($left, $y, '1. DATI DEL DETENTORE / CEDENTE');

        $y -= 16;
        $proprietario = $vfu->proprietario ?: trim(($vfu->cognome ?? '').' '.($vfu->nome ?? ''));
        $stream .= $this->labelValue($left, $y, 'Cognome e Nome', $proprietario ?: '—');

        $y -= 14;
        $stream .= $this->labelValue($left, $y, 'Codice Fiscale', strtoupper($vfu->codice_fiscale ?: '—'));
        $stream .= $this->labelValue(320, $y, 'Luogo/Data di nascita', implode(' ', array_filter([
            $vfu->luogo_nascita,
            $vfu->data_nascita?->format('d/m/Y'),
        ])) ?: '—');

        $y -= 14;
        $stream .= $this->labelValue($left, $y, 'Indirizzo', $vfu->indirizzo ?: '—');
        $comune = $vfu->comune ? $vfu->comune.($vfu->provincia ? ' ('.$vfu->provincia.')' : '') : '—';
        $stream .= $this->labelValue(320, $y, 'Comune / Provincia', $comune);

        $y -= 8;
        $stream .= $this->hRule($left, $right, $y);

        // --- Section 2: Dati del veicolo ---
        $y -= 14;
        $stream .= $this->sectionTitle($left, $y, '2. DATI DEL VEICOLO');

        $y -= 16;
        $stream .= $this->labelValue($left, $y, 'Targa', strtoupper($vfu->targa));
        $stream .= $this->labelValue(320, $y, 'Telaio (VIN)', strtoupper($vfu->telaio ?: '—'));

        $y -= 14;
        $stream .= $this->labelValue($left, $y, 'Marca', strtoupper($vfu->marca ?: '—'));
        $stream .= $this->labelValue(320, $y, 'Modello', strtoupper($vfu->modello ?: '—'));

        $y -= 14;
        $stream .= $this->labelValue($left, $y, 'Tipo veicolo', $vfu->tipo_veicolo ?: 'Autovettura');
        $stream .= $this->labelValue(320, $y, 'Nazione', $vfu->nazione ?: 'Italia');

        if ($vfu->peso_kg && (float) $vfu->peso_kg > 0) {
            $y -= 14;
            $stream .= $this->labelValue($left, $y, 'Peso (kg)', number_format((float) $vfu->peso_kg, 2, ',', '.'));
        }
        if ($vfu->codice_motore) {
            $stream .= $this->labelValue(320, $y, 'Codice motore', $vfu->codice_motore);
        }

        $y -= 8;
        $stream .= $this->hRule($left, $right, $y);

        // --- Section 3: Dati dell'autodemolitore ---
        $y -= 14;
        $stream .= $this->sectionTitle($left, $y, '3. DATI DELL\'AUTODEMOLITORE');

        $ragioneSociale = $settings->ragione_sociale ?: config('app.name', 'Autodemolitore');
        $y -= 16;
        $stream .= $this->labelValue($left, $y, 'Ragione Sociale', $ragioneSociale);

        $y -= 14;
        $stream .= $this->labelValue($left, $y, 'N. Iscrizione Albo Gestori Amb.', $settings->num_iscr_sito ?: '—');
        $stream .= $this->labelValue(320, $y, 'Codice Fiscale', strtoupper($settings->cf ?: '—'));

        $y -= 14;
        $stream .= $this->labelValue($left, $y, 'Partita IVA', $settings->piva ?: '—');

        $y -= 8;
        $stream .= $this->hRule($left, $right, $y);

        // --- Section 4: Data e luogo di consegna ---
        $y -= 14;
        $stream .= $this->sectionTitle($left, $y, '4. DATA E LUOGO DI CONSEGNA / ACCETTAZIONE');

        $y -= 16;
        $stream .= $this->labelValue($left, $y, 'Data consegna', $vfu->data_consegna?->format('d/m/Y') ?? '—');
        $stream .= $this->labelValue(320, $y, 'Data accettazione', $vfu->data_accettazione?->format('d/m/Y') ?? now()->format('d/m/Y'));

        $y -= 14;
        $stream .= $this->labelValue($left, $y, 'Luogo di consegna (sede impianto)', $ragioneSociale);

        $y -= 8;
        $stream .= $this->hRule($left, $right, $y);

        // --- Section 5: Dichiarazione ---
        $y -= 14;
        $stream .= $this->sectionTitle($left, $y, '5. DICHIARAZIONE DI AVVENUTA ACCETTAZIONE');

        $y -= 16;
        $line1 = 'Si dichiara che il VFU targa '.strtoupper($vfu->targa).' telaio '.strtoupper($vfu->telaio ?: '—');
        $line2 = 'e stato consegnato da '.($proprietario ?: '—').' ed accettato per la demolizione definitiva';
        $line3 = 'ai sensi del D.Lgs. 24 giugno 2003 n. 209 e s.m.i., D.M. 460/1999 e normativa vigente.';

        foreach ([$line1, $line2, $line3] as $line) {
            $stream .= "BT\n/F1 8.5 Tf\n{$left} {$y} Td\n(".$this->pdfStr($line).") Tj\nET\n";
            $y -= 12;
        }

        $y -= 4;
        $stream .= $this->hRule($left, $right, $y);

        // --- Section 6: Codice CER ---
        $y -= 14;
        $stream .= $this->sectionTitle($left, $y, '6. CODICE CER DI DESTINAZIONE');

        $y -= 16;
        $stream .= $this->labelValue($left, $y, 'Codice CER', '16 01 04* — Veicoli fuori uso (pericoloso)');

        $y -= 8;
        $stream .= $this->hRule($left, $right, $y);

        // --- Section 7: Firma e timbro ---
        $y -= 14;
        $stream .= $this->sectionTitle($left, $y, '7. FIRMA E TIMBRO DELL\'AUTODEMOLITORE');

        $y -= 18;
        $stream .= "BT\n/F1 9 Tf\n{$left} {$y} Td\n(".$this->pdfStr('Firma del Responsabile:').") Tj\nET\n";
        $stream .= "BT\n/F1 9 Tf\n320 {$y} Td\n(".$this->pdfStr('Timbro aziendale:').") Tj\nET\n";

        // Signature/stamp boxes
        $boxTop    = $y - 6;
        $boxBottom = $y - 54;
        $stream .= "{$left} {$boxBottom} m {$left} {$boxTop} l 270 {$boxTop} l 270 {$boxBottom} l {$left} {$boxBottom} l S\n";
        $stream .= "320 {$boxBottom} m 320 {$boxTop} l {$right} {$boxTop} l {$right} {$boxBottom} l 320 {$boxBottom} l S\n";

        $y -= 66;
        $stream .= "BT\n/F1 9 Tf\n{$left} {$y} Td\n(".$this->pdfStr('Data: _____ / _____ / _________').") Tj\nET\n";

        // --- Footer ---
        $yFoot = 52;
        $stream .= $this->hRule($left, $right, $yFoot + 10);
        $footLeft  = $ragioneSociale.($settings->piva ? ' — P.IVA '.$settings->piva : '');
        $footRight = 'Generato il '.now()->format('d/m/Y H:i').' — RENTRI CRM';
        $stream .= "BT\n/F1 7 Tf\n{$left} {$yFoot} Td\n(".$this->pdfStr($footLeft).") Tj\nET\n";
        $stream .= "BT\n/F1 7 Tf\n380 {$yFoot} Td\n(".$this->pdfStr($footRight).") Tj\nET\n";

        return $this->assemblePdf($stream, $pageW);
    }

    /** Section-title line (bold, small caps style via /F2). */
    private function sectionTitle(int $x, int $y, string $label): string
    {
        return "BT\n/F2 9 Tf\n{$x} {$y} Td\n(".$this->pdfStr($label).") Tj\nET\n";
    }

    /** Label + value on the same y. Label is small bold, value is regular. */
    private function labelValue(int $x, int $y, string $label, string $value): string
    {
        $out  = "BT\n/F2 7 Tf\n{$x} {$y} Td\n(".$this->pdfStr(strtoupper($label)).":) Tj\nET\n";
        $out .= "BT\n/F1 9 Tf\n{$x} ".($y - 10)." Td\n(".$this->pdfStr($value).") Tj\nET\n";

        return $out;
    }

    /** Horizontal rule from x1 to x2 at given y. */
    private function hRule(int $x1, int $x2, int $y): string
    {
        return "0.5 w\n{$x1} {$y} m {$x2} {$y} l S\n";
    }

    /** Assemble all PDF objects around the given content stream. */
    private function assemblePdf(string $stream, int $pageW = 595, int $pageH = 842): string
    {
        $streamLength = strlen($stream);

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageW} {$pageH}] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>\nendobj\n",
            "4 0 obj\n<< /Length {$streamLength} >>\nstream\n{$stream}endstream\nendobj\n",
            "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n",
        ];

        $pdf     = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf       .= $obj;
        }

        $xrefOffset = strlen($pdf);
        $count      = count($offsets);
        $pdf        .= "xref\n0 {$count}\n";
        $pdf        .= "0000000000 65535 f \n";

        for ($i = 1; $i < $count; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    /** Transliterate to ASCII and escape for PDF string literals. */
    private function pdfStr(string $text): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $safe  = $ascii !== false ? $ascii : $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $safe);
    }
}
