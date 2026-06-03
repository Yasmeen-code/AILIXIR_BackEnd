<?php

namespace App\Http\Requests\Screen;

use Illuminate\Foundation\Http\FormRequest;

class TargetLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disease_name' => ['required', 'string', 'max:255'],
            'top_n'        => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
