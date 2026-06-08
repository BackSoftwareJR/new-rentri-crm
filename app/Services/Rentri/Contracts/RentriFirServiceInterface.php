<?php

namespace App\Services\Rentri\Contracts;

use App\Models\Fir;
use App\Models\Trasporto;

interface RentriFirServiceInterface
{
    public function vidima(Trasporto $trasporto): Fir;

    public function nextProgressivo(string $codiceBlocco, string $numIscrSito): int;
}
