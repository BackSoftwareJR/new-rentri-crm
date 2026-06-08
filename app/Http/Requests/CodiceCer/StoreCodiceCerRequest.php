<?php

namespace App\Http\Requests\CodiceCer;

use App\Models\CodiceCer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCodiceCerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CodiceCer::class) ?? false;
    }

    public function rules(): array
    {
        return self::baseRules();
    }

    public static function baseRules(?int $codiceCerId = null): array
    {
        return [
            'codice' => [
                'required',
                'string',
                'max:20',
                Rule::unique('codici_cer', 'codice')->ignore($codiceCerId),
            ],
            'descrizione' => ['required', 'string', 'max:500'],
            'categoria' => ['required', Rule::in(['pericoloso', 'altro'])],
            'um' => ['required', Rule::in(['kg', 'litri', 'pezzi'])],
            'limite_kg' => ['nullable', 'numeric', 'min:0'],
            'rentri_codice_ref' => ['nullable', 'string', 'max:64'],
            'attivo' => ['sometimes', 'boolean'],
        ];
    }
}
