<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChemicalSearchJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'query_smiles',
        'top_k',
        'status',
        'results',
        'reason',
        'image_urls',
        'metadata',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'results' => 'array',
        'reason' => 'array',
        'image_urls' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Compounds relationship
     */
    public function compounds(): HasMany
    {
        return $this->hasMany(ChemicalCompound::class, 'job_id');
    }
}
