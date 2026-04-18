<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChemicalSearchJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'query_smiles',
        'top_k',
        'status',
        'results',
        'image_urls',
        'metadata',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'results' => 'array',
        'image_urls' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getAllImages(): array
    {
        return $this->image_urls ?? [];
    }

    /**
     * Get image by rank
     */
    public function getImageByRank(int $rank): ?string
    {
        $images = $this->image_urls ?? [];
        return $images[$rank - 1] ?? null;
    }
}
