<?php

namespace App\Domain\Mud;

use App\Models\MudDichiarazione;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MudPdfExportService
{
    public function generatePdf(MudDichiarazione $dichiarazione, MudService $mud): string
    {
        $payload = $dichiarazione->export_payload
            ?? $mud->buildExportPayload($dichiarazione);

        $righe = $payload['righe'] ?? [];
        $totali = $payload['totali'] ?? [];

        $lines = [
            'Dichiarazione MUD — Modello Unico di Dichiarazione',
            'Anno riferimento: '.$dichiarazione->anno_riferimento,
            'Stato: '.$dichiarazione->stato->value,
            'Generato il: '.now()->format('d/m/Y H:i'),
            'Codici CER: '.count($righe),
            'Carichi totali (kg): '.number_format((float) ($totali['carichi_kg'] ?? 0), 2, '.', ''),
            'Scarichi totali (kg): '.number_format((float) ($totali['scarichi_kg'] ?? 0), 2, '.', ''),
            'Saldo (kg): '.number_format((float) ($totali['saldo_kg'] ?? 0), 2, '.', ''),
        ];

        foreach (array_slice($righe, 0, 8) as $index => $riga) {
            $lines[] = sprintf(
                '%d. %s — carichi %s / scarichi %s kg',
                $index + 1,
                $riga['codice'] ?? '—',
                number_format((float) ($riga['carichi_kg'] ?? 0), 2, '.', ''),
                number_format((float) ($riga['scarichi_kg'] ?? 0), 2, '.', ''),
            );
        }

        if (count($righe) > 8) {
            $lines[] = '… '.(count($righe) - 8).' righe aggiuntive (vedi export JSON).';
        }

        return $this->buildMinimalPdf($lines);
    }

    public function filename(MudDichiarazione $dichiarazione): string
    {
        return sprintf('mud-%d.pdf', $dichiarazione->anno_riferimento);
    }

    public function downloadResponse(MudDichiarazione $dichiarazione, MudService $mud): StreamedResponse
    {
        $pdf = $this->generatePdf($dichiarazione, $mud);

        return response()->streamDownload(
            static fn () => print($pdf),
            $this->filename($dichiarazione),
            ['Content-Type' => 'application/pdf'],
        );
    }

    /**
     * @param  list<string>  $lines
     */
    private function buildMinimalPdf(array $lines): string
    {
        $stream = "BT\n/F1 11 Tf\n50 800 Td\n";
        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $stream .= "0 -16 Td\n";
            }
            $stream .= '('.$this->escapePdfString($this->toPdfSafe($line)).") Tj\n";
        }
        $stream .= "ET\n";

        $streamLength = strlen($stream);

        return implode("\n", [
            '%PDF-1.4',
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj',
            '4 0 obj << /Length '.$streamLength.' >> stream',
            $stream,
            'endstream endobj',
            '5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
            'xref',
            '0 6',
            '0000000000 65535 f ',
            '0000000009 00000 n ',
            '0000000058 00000 n ',
            '0000000115 00000 n ',
            '0000000266 00000 n ',
            sprintf('%07d 00000 n ', 266 + $streamLength + 50),
            'trailer << /Size 6 /Root 1 0 R >>',
            'startxref',
            (string) (366 + $streamLength),
            '%%EOF',
        ])."\n";
    }

    private function toPdfSafe(string $text): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return $ascii !== false ? $ascii : preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }

    private function escapePdfString(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
