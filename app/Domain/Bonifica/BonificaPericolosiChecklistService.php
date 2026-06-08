<?php

namespace App\Domain\Bonifica;

use App\Models\BonificaVfu;
use App\Models\CodiceCer;

class BonificaPericolosiChecklistService
{
    /** @var array<string, string> */
    public const MANUAL_STEPS = [
        'dpi'             => 'DPI e dispositivi di sicurezza indossati',
        'contenitori'     => 'Contenitori omologati verificati',
        'area_ventilata'  => 'Area ventilata e segnaletica verificata',
    ];

    /**
     * @param  array<string, bool>  $checklist
     * @return list<array{key: string, label: string, done: bool, required: bool, manual: bool}>
     */
    public function steps(BonificaVfu $bonifica, array $checklist = []): array
    {
        $result = [];

        foreach (self::MANUAL_STEPS as $key => $label) {
            $result[] = [
                'key'      => $key,
                'label'    => $label,
                'done'     => ! empty($checklist[$key]),
                'required' => true,
                'manual'   => true,
            ];
        }

        $quantitaOk = $this->quantitaPericolosiComplete($bonifica);
        $result[] = [
            'key'      => 'quantita',
            'label'    => 'Quantità pericolosi inserite (> 0 per ogni codice)',
            'done'     => $quantitaOk,
            'required' => true,
            'manual'   => false,
        ];

        return $result;
    }

    /**
     * @param  array<string, bool>  $checklist
     * @return array{done: int, total: int, complete: bool}
     */
    public function summary(BonificaVfu $bonifica, array $checklist = []): array
    {
        $steps = $this->steps($bonifica, $checklist);
        $required = collect($steps)->where('required', true);
        $done = $required->where('done', true)->count();

        return [
            'done'     => $done,
            'total'    => $required->count(),
            'complete' => $done === $required->count(),
        ];
    }

    /**
     * @param  array<string, bool>  $checklist
     */
    public function canAdvance(BonificaVfu $bonifica, array $checklist = []): bool
    {
        return $this->blockers($bonifica, $checklist) === [];
    }

    /**
     * @param  array<string, bool>  $checklist
     * @return list<string>
     */
    public function blockers(BonificaVfu $bonifica, array $checklist = []): array
    {
        $messages = [];

        foreach ($this->steps($bonifica, $checklist) as $step) {
            if ($step['required'] && ! $step['done']) {
                $messages[] = 'Completa: '.$step['label'];
            }
        }

        return $messages;
    }

    public function quantitaPericolosiComplete(BonificaVfu $bonifica): bool
    {
        $pericolosiIds = CodiceCer::query()
            ->where('categoria', 'pericoloso')
            ->where('attivo', true)
            ->pluck('id');

        if ($pericolosiIds->isEmpty()) {
            return true;
        }

        $bonifica->loadMissing('movimenti');

        foreach ($pericolosiIds as $pid) {
            $mov = $bonifica->movimenti->firstWhere('codice_cer_id', $pid);
            if ($mov === null || (float) $mov->quantita <= 0) {
                return false;
            }
        }

        return true;
    }
}
