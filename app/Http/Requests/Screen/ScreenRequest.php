<?php

namespace App\Http\Requests\Screen;

use Illuminate\Foundation\Http\FormRequest;

class ScreenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disease_name' => ['required', 'string', 'max:255'],
            'known_drugs'  => ['sometimes', 'array'],
            'known_drugs.*' => ['string'],
            'top_k'        => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
