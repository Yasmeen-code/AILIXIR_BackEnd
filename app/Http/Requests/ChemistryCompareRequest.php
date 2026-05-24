<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChemistryCompareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'smiles' => 'required|array|min:2',
            'smiles.*' => 'required|string|max:1000',
            'thread_id' => 'nullable|string|max:255',
        ];
    }
}
