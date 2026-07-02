<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigandExport extends Model
{
    protected $fillable = [
        'job_id',
        'status',
        'file_format',
        'smiles',
        'filename',
        'download_url'
    ];
}
