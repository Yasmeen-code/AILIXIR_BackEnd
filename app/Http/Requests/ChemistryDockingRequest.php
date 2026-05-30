<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChemistryDockingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'docking_data' => 'required|string|max:10000',
            'thread_id' => 'nullable|string|max:255',
        ];
    }
}
