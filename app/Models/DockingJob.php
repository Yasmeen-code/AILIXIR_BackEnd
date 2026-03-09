<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DockingJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'input_type',
        'smiles',
        'protein_name',
        'ligand_name',
        'protein_path',
        'ligand_path',
        'status',
        'result_data',
    ];

    protected $casts = [
        'result_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
