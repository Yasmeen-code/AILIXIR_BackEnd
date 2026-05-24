<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChemistryChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:5000',
            'thread_id' => 'nullable|string|max:255',
        ];
    }
}
