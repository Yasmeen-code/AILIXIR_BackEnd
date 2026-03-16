<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Simulation extends Model
{
    protected $fillable = [
        'user_id','protein','ligand','trajectory','video','analysis','status','progress'
    ];

    protected $casts = ['analysis'=>'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
