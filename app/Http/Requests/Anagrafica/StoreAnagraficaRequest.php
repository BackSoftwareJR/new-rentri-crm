<?php

namespace App\Http\Requests\Anagrafica;

use App\Models\Anagrafica;
use App\Rules\ValidCodiceFiscale;
use App\Rules\ValidPartitaIva;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnagraficaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Anagrafica::class) ?? false;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    public static function baseRules(?int $anagraficaId = null): array
    {
        return [
            'tipo' => ['required', Rule::in(Anagrafica::TIPI)],
            'ragione_sociale' => ['required', 'string', 'max:200'],
            'piva' => [
                'nullable',
                'string',
                'max:20',
                new ValidPartitaIva(),
                Rule::unique('anagrafiche', 'piva')->ignore($anagraficaId),
            ],
            'codice_fiscale' => ['nullable', 'string', 'max:16', new ValidCodiceFiscale()],
            'codice_sdi' => ['nullable', 'string', 'max:7'],
            'pec' => ['nullable', 'email', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'indirizzo' => ['nullable', 'string', 'max:255'],
            'cap' => ['nullable', 'string', 'max:10'],
            'citta' => ['nullable', 'string', 'max:100'],
            'provincia' => ['nullable', 'string', 'max:2'],
            'note' => ['nullable', 'string'],
            'gestisce_trasporti' => ['sometimes', 'boolean'],
            'rentri_soggetto_id' => ['nullable', 'string', 'max:64'],
            'authorizations' => ['sometimes', 'array'],
            'authorizations.*.id' => ['nullable', 'integer'],
            'authorizations.*.numero' => ['required_with:authorizations.*.rilasciata_il', 'string', 'max:100'],
            'authorizations.*.rilasciata_il' => ['required_with:authorizations.*.numero', 'date'],
            'authorizations.*.scade_il' => ['nullable', 'date', 'after_or_equal:authorizations.*.rilasciata_il'],
            'authorizations.*.tipo' => ['nullable', 'string', 'max:50'],
        ];
    }
}
