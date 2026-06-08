<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class CertificatoRottamazionePdfService
{
    public function extractFromPath(string $path): array
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($path);
        $text = $pdf->getText();
        $text = preg_replace('/\s+/', ' ', $text ?? '');
        $text = trim($text);

        $data = [
            'targa' => '',
            'telaio' => '',
            'marca' => '',
            'modello' => '',
            'nome' => '',
            'cognome' => '',
            'codice_fiscale' => '',
            'proprietario' => '',
            'indirizzo' => '',
            'comune' => '',
            'provincia' => '',
            'data_nascita' => '',
            'luogo_nascita' => '',
        ];

        $intestatarioSection = null;
        if (preg_match('/DATI INTESTATARIO(.*?)(?:DISTINTA|NOTE\s+AGGIUNTIVE|IL\s+CENTRO\s+DI\s+RACCOLTA|$)/is', $text, $m)) {
            $intestatarioSection = trim($m[1]);
        }
        $intText = $intestatarioSection ?: $text;

        $pick = static function (string $haystack, string $pattern): string {
            if (! preg_match($pattern, $haystack, $m)) {
                return '';
            }
            $val = trim($m[1] ?? '');
            $val = preg_replace('/\s+/', ' ', $val ?? '');

            return trim($val ?? '');
        };

        $toTitle = static function (string $s): string {
            $s = trim($s);
            if ($s === '') {
                return '';
            }

            return ucwords(mb_strtolower($s, 'UTF-8'));
        };

        if (preg_match('/Targa[:\s]+([A-Z]{2}[0-9]{3}[A-Z]{2})/i', $text, $m)) {
            $data['targa'] = strtoupper($m[1]);
        }
        if (preg_match('/Telaio[:\s]+([A-Z0-9]{17})/i', $text, $m)) {
            $data['telaio'] = strtoupper($m[1]);
        }
        if (preg_match('/Marca\/Modello[:\s]+(.+?)(?:\s+DATI|$)/is', $text, $m)) {
            $full = trim($m[1]);
            $parts = preg_split('/\s+/', $full);
            if (count($parts) >= 2) {
                $data['marca'] = ucwords(strtolower($parts[0]));
                $modelParts = array_slice($parts, -3);
                $data['modello'] = strtoupper(implode(' ', $modelParts));
            }
        }

        $nome = $pick($intText, '/\bNome[:\s]+(.+?)(?=\s+\bCognome\b\s*:|\s+\bComune\s+di\s+nascita\b\s*:|\s+\bProvincia\s+di\s+nascita\b\s*:|\s+\bData\s+di\s+nascita\b\s*:|\s+\bCF\b\s*:|\s+\bIndirizzo\b\s*:|\s+\bComune\b\s*:|\s+\bProvincia\b\s*:|$)/isu');
        $cognome = $pick($intText, '/\bCognome[:\s]+(.+?)(?=\s+\bComune\s+di\s+nascita\b\s*:|\s+\bProvincia\s+di\s+nascita\b\s*:|\s+\bData\s+di\s+nascita\b\s*:|\s+\bCF\b\s*:|\s+\bIndirizzo\b\s*:|\s+\bComune\b\s*:|\s+\bProvincia\b\s*:|$)/isu');
        $data['nome'] = $toTitle($nome);
        $data['cognome'] = $toTitle($cognome);

        if (! empty($data['nome']) && ! empty($data['cognome'])) {
            $data['proprietario'] = $data['nome'].' '.$data['cognome'];
        }

        $cf = $pick($intText, '/\bCF[:\s]+([A-Z0-9]{11,16})\b/isu');
        if ($cf !== '') {
            $data['codice_fiscale'] = strtoupper($cf);
        }

        $indirizzo = $pick($intText, '/\bIndirizzo[:\s]+(.+?)(?=\s+\bComune\b\s*:(?!\s*di\s+nascita)|\s+\bProvincia\b\s*:|$)/isu');
        $data['indirizzo'] = $toTitle($indirizzo);

        $comune = $pick($intText, '/\bComune\b\s*:(?!\s*di\s+nascita)\s*(.+?)(?=\s+\bProvincia\b\s*:|$)/isu');
        $data['comune'] = $toTitle($comune);

        $provincia = $pick($intText, '/\bProvincia\b\s*:\s*([A-ZÀ-Ü]{2,})\b/isu');
        if ($provincia !== '') {
            $provincia = strtoupper($provincia);
            $data['provincia'] = mb_strlen($provincia, 'UTF-8') > 2 ? mb_substr($provincia, 0, 2, 'UTF-8') : $provincia;
        }

        $data['data_nascita'] = $pick($intText, '/\bData\s+di\s+nascita[:\s]+(\d{2}\/\d{2}\/\d{4})\b/isu');

        $luogo = $pick($intText, '/\bComune\s+di\s+nascita[:\s]+(.+?)(?=\s+\bProvincia\s+di\s+nascita\b\s*:|\s+\bData\s+di\s+nascita\b\s*:|\s+\bCF\b\s*:|\s+\bIndirizzo\b\s*:|\s+\bComune\b\s*:|\s+\bProvincia\b\s*:|$)/isu');
        $data['luogo_nascita'] = $toTitle($luogo);

        return $data;
    }
}
