<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\SendOtpNotification;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendOtpJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $user;
    protected $otp;
    protected $type;

    public function __construct(User $user, int $otp, string $type)
    {
        $this->user = $user;
        $this->otp = $otp;
        $this->type = $type;
    }

    public function handle(): void
    {
        $this->user->notify(new SendOtpNotification($this->otp, $this->type));
    }
}
