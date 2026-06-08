<?php

namespace App\Domain\Demo;

use App\Domain\Rentri\RentriOnboardingService;
use App\Models\RentriSetting;

class DemoRentriPresetService
{
    /**
     * @return list<array{key: string, label: string, cf_operatore: string, num_iscr_sito: string}>
     */
    public function operatorProfiles(): array
    {
        $profiles = [];

        foreach (config('demo.operators', []) as $key => $profile) {
            $defaults = $this->mergeWithEnvOverrides($key, $profile);
            $profiles[] = [
                'key'           => $key,
                'label'         => (string) ($defaults['label'] ?? $key),
                'cf_operatore'  => $defaults['cf_operatore'],
                'num_iscr_sito' => $defaults['num_iscr_sito'],
            ];
        }

        return $profiles;
    }

    /**
     * @return array{
     *   ambiente: string,
     *   cf: string,
     *   cf_operatore: string,
     *   piva: string,
     *   ragione_sociale: string,
     *   num_iscr_sito: string
     * }
     */
    public function sandboxDefaults(?string $operatorKey = null): array
    {
        $key = $this->resolveOperatorKey($operatorKey);
        $profile = config("demo.operators.{$key}", config('demo.operators.default', []));
        $merged = $this->mergeWithEnvOverrides($key, $profile);

        return [
            'ambiente'        => 'sandbox',
            'cf'              => (string) ($merged['cf'] ?? '') ?: '00000000000',
            'cf_operatore'    => (string) ($merged['cf_operatore'] ?? '') ?: 'RSSMRA80A01H501Z',
            'piva'            => (string) ($merged['piva'] ?? '') ?: '00000000000',
            'ragione_sociale' => (string) ($merged['ragione_sociale'] ?? '') ?: 'Palestra operativa RENTRI',
            'num_iscr_sito'   => (string) ($merged['num_iscr_sito'] ?? '') ?: DemoSeedService::NUM_ISCR_SITO,
        ];
    }

    public function applySandboxPreset(
        RentriOnboardingService $onboarding,
        ?string $operatorKey = null,
    ): RentriSetting {
        return $onboarding->saveOperatorData($this->sandboxDefaults($operatorKey));
    }

    private function resolveOperatorKey(?string $operatorKey): string
    {
        $key = $operatorKey ?: 'default';
        $operators = config('demo.operators', []);

        if (! array_key_exists($key, $operators)) {
            return 'default';
        }

        return $key;
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function mergeWithEnvOverrides(string $key, array $profile): array
    {
        if ($key !== 'default') {
            return $profile;
        }

        $envPreset = config('demo.rentri_preset', []);

        return array_filter([
            'label'           => $profile['label'] ?? null,
            'cf'              => ($envPreset['cf'] ?? '') ?: ($profile['cf'] ?? null),
            'cf_operatore'    => ($envPreset['cf_operatore'] ?? '') ?: ($profile['cf_operatore'] ?? null),
            'piva'            => ($envPreset['piva'] ?? '') ?: ($profile['piva'] ?? null),
            'ragione_sociale' => ($envPreset['ragione_sociale'] ?? '') ?: ($profile['ragione_sociale'] ?? null),
            'num_iscr_sito'   => ($envPreset['num_iscr_sito'] ?? '') ?: ($profile['num_iscr_sito'] ?? null),
        ], fn ($value) => $value !== null);
    }
}
