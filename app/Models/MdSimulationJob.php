<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MdSimulationJob extends Model
{
    protected $table = 'md_simulation_jobs';

    protected $fillable = [
        'user_id',
        'remote_job_id',
        'status',
        'input_params',
        'protein_original_name',
        'ligand_original_name',
        'result_meta',
        'analysis_meta',
        'error_message',
    ];

    protected $casts = [
        'input_params' => 'array',
        'result_meta' => 'array',
        'analysis_meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }
}
