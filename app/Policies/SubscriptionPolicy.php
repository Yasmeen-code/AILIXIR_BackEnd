<?php

namespace App\Policies;

use App\Models\User;

class SubscriptionPolicy
{
    public function accessProFeatures(User $user): bool
    {
        return $user->isPro() || $user->isMax();
    }

    public function accessMaxFeatures(User $user): bool
    {
        return $user->isMax();
    }

    public function hasSubscription(User $user): bool
    {
        return $user->subscribed('default') || $user->isFree();
    }
}
