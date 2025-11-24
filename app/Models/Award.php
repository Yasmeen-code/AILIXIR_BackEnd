<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    protected $fillable = [
        'name',
        'category',
        'image_url',
        'description',
        'notable_winners',
        'country',
        'year_started',
        'website'
    ];
}
