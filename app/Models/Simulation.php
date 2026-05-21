<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Simulation extends Model
{
    protected $table = 'simulations';

    protected $fillable = [
        'user_id',
        'protein',
        'ligand',
        'status',
        'progress',
        'error_message',
        'trajectory',
        'log_file',
        'analysis',
        'force_field',
        'temperature',
        'simulation_time_ns',
        'box_size'
    ];

    protected $casts = [
        'analysis' => 'array',
        'progress' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
