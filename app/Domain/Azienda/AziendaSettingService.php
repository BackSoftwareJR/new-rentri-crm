<?php

namespace App\Domain\Azienda;

use App\Models\CompanySetting;
use App\Models\Fattura;
use Illuminate\Support\Carbon;

class AziendaSettingService
{
    public const DEFAULT_FORMATO_NUMERAZIONE = 'FT-{YEAR}-{COUNTER:3}';

    /** @var array<string, string> */
    private const KEY_MAP = [
        'ragione_sociale'              => 'company_ragione_sociale',
        'piva'                         => 'company_piva',
        'codice_fiscale'               => 'company_cf',
        'indirizzo'                    => 'company_indirizzo',
        'comune'                       => 'company_citta',
        'cap'                          => 'company_cap',
        'provincia'                    => 'company_provincia',
        'email'                        => 'company_email',
        'pec'                          => 'company_pec',
        'codice_sdi'                   => 'company_codice_sdi',
        'logo_path'                    => 'company_logo_path',
        'albo_numero'                  => 'company_num_albo',
        'telefono'                     => 'company_telefono',
        'formato_numerazione_fattura'  => 'company_formato_numerazione_fattura',
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $companyKey = self::KEY_MAP[$key] ?? $key;

        if ($key === 'formato_numerazione_fattura') {
            $value = CompanySetting::get($companyKey);

            return filled($value) ? $value : ($default ?? self::DEFAULT_FORMATO_NUMERAZIONE);
        }

        return CompanySetting::get($companyKey, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $companyKey = self::KEY_MAP[$key] ?? $key;

        CompanySetting::set($companyKey, $value);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return collect(array_keys(self::KEY_MAP))
            ->mapWithKeys(fn (string $key) => [$key => $this->get($key)])
            ->all();
    }

    public function formatoNumerazione(): string
    {
        return (string) $this->get('formato_numerazione_fattura', self::DEFAULT_FORMATO_NUMERAZIONE);
    }

    public function prossimoNumero(string $tipo = 'fattura', ?string $formatOverride = null): string
    {
        $counter = $this->contatoreProgressivo($tipo);

        return $this->applicaFormatoNumerazione(
            $formatOverride ?? $this->formatoNumerazione(),
            $counter,
            now(),
            $tipo,
        );
    }

    public function applicaFormatoNumerazione(
        string $format,
        int $counter,
        ?Carbon $date = null,
        string $tipo = 'fattura',
    ): string {
        $date   = $date ?? now();
        $format = $this->adattaFormatoPerTipo($format, $tipo);
        $result = $format;

        $result = str_replace('{YEAR}', (string) $date->year, $result);
        $result = str_replace('{YEAR_SHORT}', $date->format('y'), $result);
        $result = str_replace('{MONTH}', $date->format('m'), $result);

        $result = preg_replace_callback(
            '/\{COUNTER:(\d+)\}/',
            static fn (array $matches): string => str_pad((string) $counter, (int) $matches[1], '0', STR_PAD_LEFT),
            $result,
        ) ?? $result;

        return str_replace('{COUNTER}', (string) $counter, $result);
    }

    public function contatoreProgressivo(string $tipo = 'fattura'): int
    {
        $anno = now()->year;

        return Fattura::withTrashed()
            ->where('tipo', $tipo)
            ->whereYear('created_at', $anno)
            ->count() + 1;
    }

    private function adattaFormatoPerTipo(string $format, string $tipo): string
    {
        $prefix = match ($tipo) {
            'nota_credito' => 'NC',
            'preventivo'   => 'PV',
            default        => null,
        };

        if ($prefix !== null && str_starts_with($format, 'FT')) {
            return $prefix.substr($format, 2);
        }

        return $format;
    }
}
