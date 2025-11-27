<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
