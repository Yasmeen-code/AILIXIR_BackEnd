<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Scientist extends Model
{
    protected $fillable = [
        'name',
        'nationality',
        'birth_year',
        'death_year',
        'images',
        'bio',
        'impact',
        'field',
    ];

    protected $casts = [
        'images' => 'array',
    ];
    public function awards()
    {
        return $this->belongsToMany(Award::class, 'award_scientist')
            ->withPivot('year_won', 'contribution')
            ->withTimestamps();
    }
}
