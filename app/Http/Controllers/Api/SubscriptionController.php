<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\SwapPlanRequest;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;

class SubscriptionController extends BaseController
{
    /**
     * Get all active plans.
     */
    public function plans(Request $request)
    {
        $user = $request->user();
        $plans = Plan::where('is_active', true)->get();

        $currentPlan = $user->currentPlan;
        $subscriptionData = null;

        if ($user->subscribed('default')) {
            $subscription = $user->subscription('default');
            $subscriptionData = [
                'status' => $subscription->stripe_status,
                'on_trial' => $user->onTrial('default'),
                'cancelled' => $subscription->canceled(),
                'ends_at' => $subscription->ends_at,
                'trial_ends_at' => $subscription->trial_ends_at,
            ];
        }

        return $this->successResponse('Plans retrieved successfully', [
            'plans' => $plans,
            'current_plan' => $currentPlan,
            'subscription' => $subscriptionData,
        ]);
    }

    /**
     * Create Stripe Checkout Session.
     */
    public function checkout(CheckoutRequest $request)
    {
        $user = $request->user();
        $plan = Plan::findOrFail($request->plan_id);

        if (!$plan->is_active) {
            return $this->errorResponse('This plan is not available.', 422);
        }

        if ($plan->isFree()) {
            return $this->errorResponse('Free plan does not require payment.', 422);
        }

        if ($user->subscribed('default')) {
            if (!$user->onTrial('default')) {
                return $this->errorResponse('You already have an active subscription. Please cancel first or use swap endpoint.', 422);
            }

            try {
                $user->subscription('default')->cancelNow();
            } catch (\Exception $e) {
                Log::warning('Failed to cancel trial subscription: ' . $e->getMessage());
            }
        }

        try {
            $checkout = $user->newSubscription('default', $plan->stripe_price_id)
                ->trialDays(config('subscription.trial_days', 0))
                ->checkout([
                    'success_url' => config('app.frontend_url') . '/billing/success?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => config('app.frontend_url') . '/billing/cancel',
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan_id' => $plan->id,
                    ]
                ]);

            return $this->successResponse('Checkout URL retrieved successfully', [
                'checkout_url' => $checkout->url,
                'session_id' => $checkout->id,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe checkout error: ' . $e->getMessage());
            return $this->errorResponse('Payment service error. Please try again later.', 500);
        } catch (\Exception $e) {
            Log::error('Checkout error: ' . $e->getMessage());
            return $this->errorResponse('Could not create checkout session. Please try again.', 500);
        }
    }

    /**
     * Get subscription status.
     */
    public function status(Request $request)
    {
        $user = $request->user();

        $user->ensureHasPlan();

        $response = [
            'plan' => $user->currentPlan,
            'has_subscription' => $user->subscribed('default'),
            'on_trial' => $user->onTrial('default'),
        ];

        if ($user->subscribed('default')) {
            $subscription = $user->subscription('default');
            $response['subscription'] = [
                'stripe_id' => $subscription->stripe_id,
                'stripe_status' => $subscription->stripe_status,
                'stripe_price' => $subscription->stripe_price,
                'quantity' => $subscription->quantity,
                'trial_ends_at' => $subscription->trial_ends_at,
                'ends_at' => $subscription->ends_at,
                'created_at' => $subscription->created_at,
                'updated_at' => $subscription->updated_at,
                'cancelled' => $subscription->canceled(),
                'on_grace_period' => $subscription->onGracePeriod(),
                'ended' => $subscription->ended(),
            ];

            if (!$subscription->canceled() && !$subscription->onGracePeriod()) {
                try {
                    $nextPayment = $subscription->asStripeSubscription()->current_period_end;
                    $response['subscription']['next_payment_at'] = date('Y-m-d H:i:s', $nextPayment);
                } catch (\Exception $e) {
                    Log::warning('Failed to retrieve next payment date: ' . $e->getMessage());
                }
            }
        }

        return $this->successResponse('Subscription status retrieved successfully', $response);
    }

    /**
     * Change plan.
     */
    public function swap(SwapPlanRequest $request)
    {
        $user = $request->user();
        $plan = Plan::findOrFail($request->plan_id);

        if (!$plan->is_active) {
            return $this->errorResponse('This plan is not available.', 422);
        }

        if ($plan->isFree()) {
            return $this->errorResponse('Cannot swap to free plan. Please cancel your subscription first.', 422);
        }

        if (!$user->subscribed('default')) {
            return $this->errorResponse('No active subscription found.', 404);
        }

        if ($user->onTrial('default')) {
            return $this->errorResponse('Cannot swap plan during trial period. Please wait for trial to end or cancel first.', 422);
        }

        $currentPlan = $user->currentPlan;
        if ($currentPlan->stripe_price_id === $plan->stripe_price_id) {
            return $this->errorResponse('You are already subscribed to this plan.', 422);
        }

        try {
            $subscription = $user->subscription('default');

            if ($subscription->onGracePeriod()) {
                $subscription->resume();
            }

            $subscription->swap($plan->stripe_price_id);

            return $this->successResponse('Plan changed successfully', [
                'new_plan' => $plan,
                'effective_date' => now()->toDateTimeString(),
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe swap error: ' . $e->getMessage());
            return $this->errorResponse('Payment service error. Please try again later.', 500);
        } catch (\Exception $e) {
            Log::error('Swap plan error: ' . $e->getMessage());
            return $this->errorResponse('Could not change plan. Please try again.', 500);
        }
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request)
    {
        $user = $request->user();

        if ($user->currentPlan->isFree()) {
            return $this->errorResponse('Free plan cannot be cancelled.', 422);
        }

        if (!$user->subscribed('default')) {
            return $this->errorResponse('No active subscription found.', 404);
        }

        $subscription = $user->subscription('default');

        if ($subscription->canceled()) {
            return $this->errorResponse('Subscription is already cancelled.', 422);
        }

        try {
            $subscription->cancel();

            return $this->successResponse('Subscription cancelled successfully. You will have access until ' . $subscription->ends_at->format('Y-m-d'), [
                'ends_at' => $subscription->ends_at,
                'on_grace_period' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Cancel subscription error: ' . $e->getMessage());
            return $this->errorResponse('Could not cancel subscription. Please try again.', 500);
        }
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
            return $this->errorResponse('No active subscription found.', 404);
        }

        $subscription = $user->subscription('default');

        if (!$subscription->canceled() || !$subscription->onGracePeriod()) {
            return $this->errorResponse('Subscription cannot be resumed. It is either active or already ended.', 422);
        }

        try {
            $subscription->resume();

            return $this->successResponse('Subscription resumed successfully', [
                'resumes_at' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Resume subscription error: ' . $e->getMessage());
            return $this->errorResponse('Could not resume subscription. Please try again.', 500);
        }
    }

    /**
     * Get user invoices.
     */
    public function invoices(Request $request)
    {
        $user = $request->user();

        if (!$user->subscribed('default')) {
            return $this->errorResponse('No active subscription found.', 404);
        }

        try {
            if (!$user->stripe_id) {
                return $this->successResponse('No invoices found', [
                    'invoices' => [],
                    'count' => 0
                ]);
            }

            $invoices = $user->invoices();

            $formattedInvoices = $invoices->map(function ($invoice) {
                $currency = strtoupper($invoice->currency ?? 'USD');

                return [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'date' => $invoice->date()->toDateTimeString(),
                    'amount' => $invoice->total(),
                    'currency' => $currency,
                    'status' => $invoice->status,
                    'hosted_invoice_url' => $invoice->hosted_invoice_url,
                ];
            });

            return $this->successResponse('Invoices fetched successfully', [
                'invoices' => $formattedInvoices,
                'count' => $formattedInvoices->count(),
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe invoices error: ' . $e->getMessage());
            return $this->errorResponse('Could not fetch invoices from payment service.', 500);
        } catch (\Exception $e) {
            Log::error('Invoices fetch error: ' . $e->getMessage());
            return $this->errorResponse('Could not fetch invoices. Please try again.', 500);
        }
    }

    /**
     * Get billing portal URL.
     */
    public function billingPortal(Request $request)
    {
        $user = $request->user();

        if (!$user->subscribed('default')) {
            return $this->errorResponse('No active subscription found.', 404);
        }

        try {
            if (!$user->stripe_id) {
                return $this->errorResponse('No Stripe customer found. Please subscribe first.', 404);
            }

            $returnUrl = config('app.frontend_url') . '/dashboard';
            $portalUrl = $user->billingPortalUrl($returnUrl);

            return $this->successResponse('Billing portal link created successfully', [
                'billing_portal_url' => $portalUrl,
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe billing portal error: ' . $e->getMessage());
            return $this->errorResponse('Could not create billing portal session.', 500);
        } catch (\Exception $e) {
            Log::error('Billing portal error: ' . $e->getMessage());
            return $this->errorResponse('Could not create billing portal link. Please try again.', 500);
        }
    }
}
