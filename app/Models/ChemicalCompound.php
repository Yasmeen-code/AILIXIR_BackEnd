<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChemicalCompound extends Model
{
    protected $fillable = [
        'job_id',
        'rank',
        'smiles',
        'name',
        'cid',
        'similarity',
        'explanation',
        'image_url',
    ];

    protected $casts = [
        'similarity' => 'float',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ChemicalSearchJob::class, 'job_id');
    }
}
