<?php

namespace App\Http\Requests\Anagrafica;

use App\Models\Anagrafica;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAnagraficaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $anagrafica = $this->route('anagrafica');

        return $anagrafica instanceof Anagrafica
            && ($this->user()?->can('update', $anagrafica) ?? false);
    }

    public function rules(): array
    {
        $anagrafica = $this->route('anagrafica');
        $id = $anagrafica instanceof Anagrafica ? $anagrafica->id : null;

        return StoreAnagraficaRequest::baseRules($id);
    }
}
