<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigandExport extends Model
{
    protected $fillable = [
        'user_id',
        'job_id',
        'status',
        'file_format',
        'smiles',
        'filename'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
