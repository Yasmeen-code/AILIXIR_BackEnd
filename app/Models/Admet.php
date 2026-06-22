<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admet extends Model
{
    protected $table = 'admets';

    protected $fillable = [
        'smiles',
        'absorption',
        'distribution',
        'metabolism',
        'excretion',
        'toxicity',
    ];
}
