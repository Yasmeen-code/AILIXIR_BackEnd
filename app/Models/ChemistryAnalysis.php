<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChemistryAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chemistry_thread_id',
        'type',
        'input_data',
        'response',
        'properties',
        'drug_likeness',
        'admet',
        'processing_time_ms',
        'status',
        'error_message',
    ];

    protected $casts = [
        'properties' => 'array',
        'drug_likeness' => 'array',
        'admet' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChemistryThread::class, 'chemistry_thread_id');
    }
}
