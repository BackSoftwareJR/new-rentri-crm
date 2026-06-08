<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RentriRegistro extends Model
{
    protected $table = 'rentri_registri';

    protected $fillable = [
        'anno',
        'vidimato',
        'codice_registro_rentri',
    ];

    protected function casts(): array
    {
        return [
            'anno'     => 'integer',
            'vidimato' => 'boolean',
        ];
    }
}
