<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TargetLookup extends Model
{
    protected $fillable = ['user_id', 'input', 'output'];

    protected $casts = [
        'input'  => 'array',
        'output' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
