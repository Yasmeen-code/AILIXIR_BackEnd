<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Award extends Model
{
    protected $fillable = [
        'name',
        'category',
        'images',
        'description',
        'notable_winners',
        'country',
        'year_started',
        'website'
    ];

    protected $casts = [
        'images' => 'array',
    ];

    public function scientists(): BelongsToMany
    {
        return $this->belongsToMany(Scientist::class)
            ->withPivot('year_won', 'contribution')
            ->withTimestamps();
    }
}
