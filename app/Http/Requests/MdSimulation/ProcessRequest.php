<?php

namespace App\Http\Requests\MdSimulation;

use Illuminate\Foundation\Http\FormRequest;

class ProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'protein' => 'required|file|mimes:pdb|max:51200',
            'ligand' => 'required|file|mimes:pdb|max:10240',
            'force_field' => 'nullable|string|in:ff19SB,ff14SB',
            'net_charge' => 'nullable|integer|min:-10|max:10',
            'box_size' => 'nullable|numeric|min:6|max:30',
            'remove_waters' => 'nullable|boolean',
            'add_hydrogens' => 'nullable|boolean',
            'equil_time_ns' => 'nullable|numeric|min:0.1|max:50',
            'sim_time_ns' => 'nullable|numeric|min:0.01|max:100',
            'n_strides' => 'nullable|integer|min:1|max:10',
            'temperature_k' => 'nullable|numeric|min:200|max:400',
            'pressure_bar' => 'nullable|numeric|min:0.5|max:5',
            'ion_type' => 'nullable|string|in:NaCl,KCl',
            'salt_conc' => 'nullable|numeric|min:0|max:5',
            'dt_fs' => 'nullable|integer|min:1|max:10',
        ];
    }
}
