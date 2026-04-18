<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChemicalSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'smiles' => 'required|string',
            'top_k' => 'nullable|integer|min:1|max:50',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();

        if (!isset($validated['top_k'])) {
            $validated['top_k'] = 3;
        }

        return $key ? ($validated[$key] ?? $default) : $validated;
    }
}
