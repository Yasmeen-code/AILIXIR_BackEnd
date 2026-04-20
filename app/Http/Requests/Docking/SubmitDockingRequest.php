<?php

namespace App\Http\Requests\Docking;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitDockingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'protein_name' => 'required|string|max:255',
            'ligand_name'  => 'required|string|max:255',
            'ligand_file'  => 'nullable|file|required_without:ligand_smiles',
            'ligand_smiles' => 'nullable|string|max:2000|required_without:ligand_file',
            'protein_file' => 'required|file',
            'center_x' => 'required|numeric',
            'center_y' => 'required|numeric',
            'center_z' => 'required|numeric',
            'box_size_x' => 'required|numeric',
            'box_size_y' => 'required|numeric',
            'box_size_z' => 'required|numeric',
            'exhaustiveness' => 'nullable|integer',
            'n_poses' => 'nullable|integer',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->hasFile('ligand_file') && $this->filled('ligand_smiles')) {
                $validator->errors()->add(
                    'ligand_input',
                    'Provide either a ligand file or a SMILES string, not both.'
                );
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'data' => ['errors' => $validator->errors()],
            ], 422)
        );
    }
}
