<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    protected function handleCheckoutSessionCompleted(array $payload)
    {
        return $this->successMethod();
    }

    protected function handleCustomerSubscriptionCreated(array $payload)
    {
        parent::handleCustomerSubscriptionCreated($payload);

        $this->syncUserPlan($payload);

        return $this->successMethod();
    }

    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        parent::handleCustomerSubscriptionUpdated($payload);

        $this->syncUserPlan($payload);

        return $this->successMethod();
    }

    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        parent::handleCustomerSubscriptionDeleted($payload);

        $customerId = $payload['data']['object']['customer'];

        $user = User::where('stripe_id', $customerId)->first();

        if (!$user) {
            return $this->successMethod();
        }

        $freePlan = Plan::where('type', 'free')->first();

        if ($freePlan) {

            $user->update([
                'current_plan_id' => $freePlan->id,
                'subscription_paused' => false,
                'subscription_status' => 'cancelled',
            ]);
        }

        return $this->successMethod();
    }

    protected function handleCustomerSubscriptionPaused(array $payload)
    {
        $customerId = $payload['data']['object']['customer'];

        User::where('stripe_id', $customerId)->update(['subscription_paused' => true, 'subscription_status' => 'paused',]);

        return $this->successMethod();
    }

    protected function handleCustomerSubscriptionResumed(array $payload)
    {
        $customerId = $payload['data']['object']['customer'];

        User::where('stripe_id', $customerId)->update(['subscription_paused' => false, 'subscription_status' => 'active']);

        return $this->successMethod();
    }

    protected function handleInvoicePaymentSucceeded(array $payload)
    {
        $customerId = $payload['data']['object']['customer'];

        User::where('stripe_id', $customerId)->update(['last_payment_at' => Carbon::now(), 'subscription_status' => 'active']);

        return $this->successMethod();
    }

    protected function handleInvoicePaymentFailed(array $payload)
    {
        $customerId = $payload['data']['object']['customer'];

        User::where('stripe_id', $customerId)->update(['last_payment_failed_at' => Carbon::now(), 'subscription_status' => 'payment_failed',]);

        return $this->successMethod();
    }

    private function syncUserPlan(array $payload): void
    {
        $subscription = $payload['data']['object'];

        $customerId = $subscription['customer'];

        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;

        if (!$priceId) {
            return;
        }

        $user = User::where('stripe_id', $customerId)->first();

        if (!$user) {
            return;
        }

        $plan = Plan::where('stripe_price_id', $priceId)->first();

        if (!$plan) {
            return;
        }

        $user->update([
            'current_plan_id' => $plan->id,
            'subscription_paused' => false,
            'subscription_status' => 'active',
        ]);
    }
}
