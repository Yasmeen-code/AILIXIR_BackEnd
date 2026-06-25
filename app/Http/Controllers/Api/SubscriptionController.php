<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\SwapPlanRequest;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends BaseController
{
    /**
     * Get all active plans.
     */
    public function plans(Request $request)
    {
        $user = $request->user();
        $plans = Plan::where('is_active', true)->get();

        return $this->successResponse('Plans retrieved successfully', [
            'plans' => $plans,
            'current_plan' => $user->currentPlan
        ]);
    }

    /**
     * Create Stripe Checkout Session.
     */
    public function checkout(CheckoutRequest $request)
    {
        $user = $request->user();

        $plan = Plan::findOrFail($request->plan_id);

        if ($plan->isFree()) {
            return $this->errorResponse('Free plan does not require payment.', 422);
        }

        if ($user->subscribed('default') && !$user->onTrial('default')) {
            return $this->errorResponse('User already has an active subscription.', 422);
        }

        $checkout = $user->newSubscription('default', $plan->stripe_price_id)
            ->checkout([
                'success_url' => config('app.frontend_url') . '/billing/success',
                'cancel_url' => config('app.frontend_url') . '/billing/cancel'
            ]);

        return $this->successResponse('Checkout URL retrieved successfully', [
            'checkout_url' => $checkout->url,
        ]);
    }

    /**
     * Get subscription status.
     */
    public function status(Request $request)
    {
        $user = $request->user();

        $user->ensureHasPlan();

        if ($user->isFree()) {
            return $this->successResponse('Subscription status retrieved successfully', [
                'plan' => $user->currentPlan,
                'has_subscription' => $user->subscribed('default'),
                'on_trial' => $user->onTrial('default'),
            ]);
        }

        $subscription = $user->subscription('default');

        return $this->successResponse('Subscription status retrieved successfully', [
            'plan' => $user->currentPlan,
            'has_subscription' => $user->subscribed('default'),
            'subscription_status' => $subscription->stripe_status,
            'on_trial' => $user->onTrial('default'),
            'cancelled' => $subscription->canceled(),
            'ends_at' => $subscription->ends_at,
        ]);
    }

    /**
     * Change plan.
     */
    public function swap(SwapPlanRequest $request)
    {
        $user = $request->user();

        $plan = Plan::findOrFail($request->plan_id);

        if ($plan->isFree()) {
            return $this->errorResponse('Cannot swap to free plan.', 422);
        }

        if (!$user->subscribed('default')) {
            return $this->errorResponse('No active subscription.', 404);
        }

        if ($user->currentPlan->stripe_price_id === $plan->stripe_price_id) {
            return $this->errorResponse('You are already subscribed to this plan.', 422);
        }

        try {
            $user->subscription('default')->swap($plan->stripe_price_id);
            return $this->successResponse('Plan change requested successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        $user = $request->user();

        if ($user->currentPlan->isFree()) {
            return $this->errorResponse('Cannot cancel free plan.', 422);
        }

        if (!$user->subscribed('default')) {
            return $this->errorResponse('No active subscription.', 404);
        }

        $subscription = $user->subscription('default');

        if ($subscription->canceled()) {
            return $this->errorResponse('Subscription already cancelled.', 422);
        }

        $subscription->cancel();

        return $this->successResponse('Subscription cancelled successfully');
    }

    /**
     * Resume subscription.
     */
    public function resume(Request $request)
    {
        $user = $request->user();

        if ($user->currentPlan->isFree()) {
            return $this->errorResponse('Cannot resume free plan.', 422);
        }

        if (!$user->subscribed('default')) {
            return $this->errorResponse('No active subscription.', 404);
        }

        $user->subscription('default')->resume();

        return $this->successResponse('Subscription resumed successfully');
    }

    /**
     * Get user invoices.
     */
    public function invoices(Request $request)
    {
        $user = $request->user();

        if ($user->currentPlan->isFree()) {
            return $this->errorResponse('Cannot get invoices for free plan.', 422);
        }

        if (!$user->subscribed('default')) {
            return $this->errorResponse('No active subscription.', 404);
        }

        try {
            if (!$user->stripe_id) {
                return $this->errorResponse('You do not have any invoices', 404);
            }

            $invoices = $user->invoices();

            $formattedInvoices = $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'datetime' => $invoice->date()->toDateTimeString(),
                    'amount' => $invoice->total(),
                    'currency' => $invoice->currency,
                    'paid' => $invoice->paid,
                    'status' => $invoice->status,
                    'number' => $invoice->number,
                ];
            });

            return $this->successResponse('Invoices fetched successfully', [
                'invoices' => $formattedInvoices,
                'count' => $formattedInvoices->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Invoices fetch error: ' . $e->getMessage());

            return $this->errorResponse('Invoices fetch error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get billing portal URL.
     */
    public function billingPortal(Request $request)
    {
        $user = $request->user();

        if ($user->currentPlan->isFree()) {
            return $this->errorResponse('Cannot get billing portal for free plan.', 422);
        }

        if (!$user->subscribed('default')) {
            return $this->errorResponse('No active subscription.', 404);
        }

        try {
            if (!$user->stripe_id) {
                return $this->errorResponse('You do not have an active subscription to use the billing gateway', 400);
            }

            $portalUrl = $user->billingPortalUrl(url('/'));

            return $this->successResponse('Billing portal link successfully created', [
                'billing_portal_url' => $portalUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Billing portal error: ' . $e->getMessage());

            return $this->errorResponse('Billing portal error: ' . $e->getMessage(), 500);
        }
    }
}
