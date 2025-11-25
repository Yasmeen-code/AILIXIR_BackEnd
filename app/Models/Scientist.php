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
        'image_url',
        'bio',
        'impact',
        'field',
    ];
}
