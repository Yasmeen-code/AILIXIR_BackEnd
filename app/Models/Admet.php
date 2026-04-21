<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admet extends Model
{
    protected $table = 'admets';

    protected $fillable = [
        'smiles',
        'user_id',
        'absorption',
        'distribution',
        'metabolism',
        'excretion',
        'toxicity',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
