<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiJob extends Model
{
    protected $table = 'ai_jobs';

    protected $fillable = [
        'user_id',
        'job_id',
        'status',
        'stage',
        'preset',
        'num_molecules',
        'return_top_k',
        'docking_mode',
        'dock_top_k',
        'summary',
        'files',
        'ligands',
    ];

    protected $casts = [
        'summary' => 'array',
        'files' => 'array',
        'ligands' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'running',
        'preset' => 'egfr_generator',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function getSummaryStats(): array
    {
        return [
            'num_requested' => $this->summary['num_requested'] ?? 0,
            'num_generated' => $this->summary['num_generated'] ?? 0,
            'num_valid' => $this->summary['num_valid'] ?? 0,
            'num_returned' => $this->summary['num_returned'] ?? 0,
            'num_docked' => $this->summary['num_docked'] ?? 0,
        ];
    }
}
