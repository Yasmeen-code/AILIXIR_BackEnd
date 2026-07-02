<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChemistryCsvJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chemistry_thread_id',
        'job_id',
        'filename',
        'analysis_type',
        'total_rows',
        'completed_rows',
        'failed_rows',
        'progress_percent',
        'status',
        'result_file_path',
        'result_content',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
