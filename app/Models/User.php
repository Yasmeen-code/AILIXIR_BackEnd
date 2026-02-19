<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Researcher;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_otp',
        'password_reset_otp', 
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'email_verification_otp_expires_at' => 'datetime', 
            'password_reset_otp_expires_at' => 'datetime', 
            'is_verified' => 'boolean',
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
}