<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class RunAiRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'num_smiles' => 'required|integer',
            'top_k' => 'required|integer',
            'Docking' => 'required|numeric',
            'DeepPurpose_Affinity' => 'required|numeric',
            'SA' => 'required|numeric',
            'TPSA' => 'required|numeric',
            'MW' => 'required|numeric'
        ];
    }
}
