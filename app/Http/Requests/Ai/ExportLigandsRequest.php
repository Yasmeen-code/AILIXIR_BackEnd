<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class ExportLigandsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'smiles' => 'required|string',
            'format' => 'required|string|in:pdbqt,pdb,mol2',
        ];
    }
}
