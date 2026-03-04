<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $this->user()->id,
            'password' => 'sometimes|string|min:6|confirmed',
            'specialization' => 'sometimes|string|max:255',
            'university' => 'sometimes|string|max:255',
            'years_of_experience' => 'sometimes|integer|min:0',
            'bio' => 'sometimes|string',
            'photo' => 'sometimes|file|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
