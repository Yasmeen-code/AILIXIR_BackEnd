<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Researcher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialization',
        'university',
        'years_of_experience',
        'bio',
        'photo',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
