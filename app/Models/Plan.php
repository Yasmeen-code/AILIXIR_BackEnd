<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'billing_period',
        'price',
        'currency',
        'stripe_price_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class,'current_plan_id');
    }

    public function isFree(): bool
    {
        return $this->type === 'free';
    }

    public function isPro(): bool
    {
        return $this->type === 'pro';
    }

    public function isMax(): bool
    {
        return $this->type === 'max';
    }
}
