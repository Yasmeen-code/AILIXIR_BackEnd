<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan_id.required' => 'Plan is required.',
            'plan_id.exists' => 'Selected plan does not exist.',
        ];
    }
}
