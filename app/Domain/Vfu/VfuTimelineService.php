<?php

namespace App\Domain\Vfu;

use App\Enums\VfuStato;
use App\Models\VfuRegistration;

final class VfuTimelineService
{
    /**
     * @return list<array{key: string, label: string, status: string, date: ?string, hint: ?string}>
     */
    public function steps(VfuRegistration $vfu): array
    {
        if ($vfu->stato === VfuStato::Annullato) {
            return [
                [
                    'key'    => 'annullato',
                    'label'  => 'Pratica annullata',
                    'status' => 'cancelled',
                    'date'   => $vfu->updated_at?->format('d/m/Y'),
                    'hint'   => 'Flusso interrotto',
                ],
            ];
        }

        $rank = $this->statoRank($vfu->stato);
        $currentKey = $this->currentStepKey($vfu->stato);

        $definitions = [
            [
                'key'  => 'registrazione',
                'label'=> 'Registrazione',
                'rank' => 1,
                'date' => $vfu->created_at?->format('d/m/Y'),
                'hint' => 'Dati veicolo e documenti',
            ],
            [
                'key'  => 'accettazione',
                'label'=> 'Accettazione',
                'rank' => 2,
                'date' => $vfu->data_accettazione?->format('d/m/Y'),
                'hint' => 'Conferma accettazione in impianto',
            ],
            [
                'key'  => 'bonifica',
                'label'=> 'Bonifica',
                'rank' => 4,
                'date' => $vfu->bonifica_pericolosi_completata_at?->format('d/m/Y'),
                'hint' => 'Smontaggio e pericolosi',
            ],
            [
                'key'  => 'bonificato',
                'label'=> 'Bonificato',
                'rank' => 5,
                'date' => null,
                'hint' => 'Veicolo bonificato',
            ],
            [
                'key'  => 'chiusura',
                'label'=> 'Chiusura pratica',
                'rank' => 6,
                'date' => $vfu->data_invio_agenzia?->format('d/m/Y H:i'),
                'hint' => 'Invio agenzia o rottamazione',
            ],
        ];

        return collect($definitions)
            ->map(function (array $def) use ($rank, $currentKey) {
                $status = match (true) {
                    $rank > $def['rank'] => 'completed',
                    $def['key'] === $currentKey => 'current',
                    default => 'pending',
                };

                return [
                    'key'    => $def['key'],
                    'label'  => $def['label'],
                    'status' => $status,
                    'date'   => $def['date'],
                    'hint'   => $def['hint'],
                ];
            })
            ->all();
    }

    private function statoRank(VfuStato $stato): int
    {
        return match ($stato) {
            VfuStato::Bozza, VfuStato::InAccettazione => 1,
            VfuStato::Accettato                       => 2,
            VfuStato::AttesaBonifica                  => 3,
            VfuStato::InBonifica                      => 4,
            VfuStato::Bonificato                      => 5,
            VfuStato::InviatoAgenzia                  => 6,
            VfuStato::Rottamato                       => 7,
            VfuStato::Annullato                       => -1,
        };
    }

    private function currentStepKey(VfuStato $stato): string
    {
        return match ($stato) {
            VfuStato::Bozza, VfuStato::InAccettazione => 'registrazione',
            VfuStato::Accettato                       => 'accettazione',
            VfuStato::AttesaBonifica, VfuStato::InBonifica => 'bonifica',
            VfuStato::Bonificato                      => 'bonificato',
            VfuStato::InviatoAgenzia, VfuStato::Rottamato => 'chiusura',
            VfuStato::Annullato                       => 'annullato',
        };
    }
}
