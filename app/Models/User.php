<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Researcher;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verification_otp',
        'email_verification_otp_expires_at',
        'is_verified',
        'password_reset_otp',
        'password_reset_otp_expires_at',
        'last_otp_sent_at',
        'current_plan_id',
    ];
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_otp',
        'password_reset_otp',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_verification_otp_expires_at' => 'datetime',
            'password_reset_otp_expires_at' => 'datetime',
            'is_verified' => 'boolean',
            'last_otp_sent_at' => 'datetime',

        ];
    }

    public function researcher()
    {
        return $this->hasOne(Researcher::class);
    }

    public function isEmailOtpValid(string $otp): bool
    {
        return $this->email_verification_otp === $otp
            && $this->email_verification_otp_expires_at
            && $this->email_verification_otp_expires_at->isFuture();
    }

    public function isPasswordResetOtpValid(string $otp): bool
    {
        return $this->password_reset_otp === $otp
            && $this->password_reset_otp_expires_at
            && $this->password_reset_otp_expires_at->isFuture();
    }


    public function clearEmailOtp(): void
    {
        $this->update([
            'email_verification_otp' => null,
            'email_verification_otp_expires_at' => null,
        ]);
    }


    public function clearPasswordResetOtp(): void
    {
        $this->update([
            'password_reset_otp' => null,
            'password_reset_otp_expires_at' => null,
        ]);
    }

    public function simulations()
    {
        return $this->hasMany(Simulation::class);
    }

    public function aiJobs()
    {
        return $this->hasMany(AiJob::class);
    }

    public function chemistryThreads(): HasMany
    {
        return $this->hasMany(ChemistryThread::class);
    }

    public function chemistryAnalyses(): HasMany
    {
        return $this->hasMany(ChemistryAnalysis::class);
    }

    public function chemistryCsvJobs(): HasMany
    {
        return $this->hasMany(ChemistryCsvJob::class);
    }

    public function currentPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function isFree(): bool
    {
        return $this->currentPlan?->type === 'free';
    }

    public function isPro(): bool
    {
        return $this->currentPlan?->type === 'pro';
    }

    public function isMax(): bool
    {
        return $this->currentPlan?->type === 'max';
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscribed('default');
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {

            $freePlan = Plan::where(
                'type',
                'free'
            )->first();

            if ($freePlan) {
                $user->update([
                    'current_plan_id' => $freePlan->id,
                ]);
            }
        });
    }

    public function ensureHasPlan(): void
    {
        if ($this->current_plan_id !== null) {
            return;
        }

        $freePlan = Plan::where('type', 'free')->first();

        if ($freePlan) {
            $this->update([
                'current_plan_id' => $freePlan->id,
            ]);
        }
    }

    public function syncCurrentPlan(): void
    {
        $subscription = $this->subscription('default');

        if (! $subscription) {
            return;
        }

        $plan = Plan::where(
            'stripe_price_id',
            $subscription->stripe_price
        )->first();

        if ($plan && $this->current_plan_id !== $plan->id) {
            $this->update([
                'current_plan_id' => $plan->id,
            ]);
        }
    }
}
