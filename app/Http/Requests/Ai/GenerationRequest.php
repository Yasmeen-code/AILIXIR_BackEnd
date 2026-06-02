<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class GenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'num_molecules' => 'required|integer|min:1',
            'return_top_k' => 'required|integer|min:1|lte:num_molecules',
            'docking_mode' => 'required|string|in:top_k,off,all',
            'dock_top_k' => 'exclude_unless:docking_mode,top_k|required|integer|min:0|lte:return_top_k',
        ];
    }

    public function messages(): array
    {
        return [
            'dock_top_k.required' => 'Dock top K is required when docking mode is top_k',
            'dock_top_k.lte' => 'Dock top K must be less than or equal to return top K',
        ];
    }
}
