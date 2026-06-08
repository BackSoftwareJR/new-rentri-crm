<?php

namespace App\Http\Requests\CodiceCer;

use App\Models\CodiceCer;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCodiceCerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $codice = $this->route('codiceCer');

        return $codice instanceof CodiceCer
            && ($this->user()?->can('update', $codice) ?? false);
    }

    public function rules(): array
    {
        $codice = $this->route('codiceCer');
        $id = $codice instanceof CodiceCer ? $codice->id : null;

        return StoreCodiceCerRequest::baseRules($id);
    }
}
