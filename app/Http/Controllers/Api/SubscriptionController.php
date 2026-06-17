<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends BaseController
{
    /**
     * إنشاء جلسة دفع جديدة للاشتراك
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'price_id' => 'required|string',
            'trial_days' => 'nullable|integer|min:0|max:30',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
        ]);

        $user = $request->user();

        if ($user->subscribed()) {
            return response()->json([
                'success' => false,
                'message' => 'أنت مشترك بالفعل في هذه الخدمة.'
            ], 400);
        }

        try {
            $subscription = $user->newSubscription('default', $request->price_id);

            if ($request->has('trial_days') && $request->trial_days > 0) {
                $subscription->trialDays($request->trial_days);
            }

            $subscription->allowPromotionCodes();

            $checkout = $subscription->checkout([
                'success_url' => $request->success_url . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $request->cancel_url,
                'metadata' => [
                    'user_id' => $user->id,
                    'platform' => 'mobile_app'
                ]
            ]);

            return response()->json([
                'success' => true,
                'checkout_url' => $checkout->url,
                'session_id' => $checkout->id,
                'message' => 'تم إنشاء جلسة الدفع بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('Subscription checkout error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء جلسة الدفع: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على حالة الاشتراك الحالي
     */
    public function status(Request $request)
    {
        $user = $request->user();

        $subscription = $user->subscription('default');

        if (!$subscription || !$subscription->valid()) {
            return response()->json([
                'success' => true,
                'subscribed' => false,
                'status' => 'inactive',
                'message' => 'ليس لديك اشتراك نشط'
            ]);
        }

        if ($subscription->onTrial()) {
            $status = 'trialing';
        } elseif ($subscription->canceled() && !$subscription->ended()) {
            $status = 'canceled';
        } elseif ($subscription->ended()) {
            $status = 'ended';
        } elseif ($subscription->incomplete()) {
            $status = 'incomplete';
        } elseif ($subscription->pastDue()) {
            $status = 'past_due';
        } else {
            $status = 'active';
        }

        return response()->json([
            'success' => true,
            'subscribed' => true,
            'status' => $status,
            'plan' => $subscription->stripe_price,
            'start_date' => $subscription->created_at,
            'end_date' => $subscription->ends_at,
            'on_trial' => $subscription->onTrial(),
            'trial_ends_at' => $subscription->trial_ends_at,
            'canceled' => $subscription->canceled(),
            'ended' => $subscription->ended(),
            'incomplete' => $subscription->incomplete(),
            'past_due' => $subscription->pastDue(),
        ]);
    }

    /**
     * إلغاء الاشتراك
     */
    public function cancel(Request $request)
    {
        $user = $request->user();

        try {
            $subscription = $user->subscription('default');

            if (!$subscription || !$subscription->valid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك اشتراك نشط لإلغائه'
                ], 400);
            }

            $subscription->cancel();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الاشتراك بنجاح. سيستمر حتى نهاية الفترة الحالية.',
                'ends_at' => $subscription->ends_at
            ]);
        } catch (\Exception $e) {
            Log::error('Subscription cancel error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء الاشتراك: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * استئناف الاشتراك بعد الإلغاء
     */
    public function resume(Request $request)
    {
        $user = $request->user();

        try {
            $subscription = $user->subscription('default');

            if (!$subscription || !$subscription->canceled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد اشتراك ملغى لاستئنافه'
                ], 400);
            }

            $subscription->resume();

            return response()->json([
                'success' => true,
                'message' => 'تم استئناف الاشتراك بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('Subscription resume error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء استئناف الاشتراك: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على رابط بوابة الفواتير
     */
    public function billingPortal(Request $request)
    {
        $user = $request->user();

        try {
            if (!$user->stripe_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك اشتراك فعال لإدارة الفواتير'
                ], 400);
            }

            $portalUrl = $user->billingPortalUrl(url('/'));

            return response()->json([
                'success' => true,
                'billing_portal_url' => $portalUrl,
                'message' => 'تم إنشاء رابط بوابة الفواتير بنجاح'
            ]);
        } catch (\Exception $e) {
            Log::error('Billing portal error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء رابط بوابة الفواتير: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث طريقة الدفع
     */
    public function updatePaymentMethod(Request $request)
    {
        $user = $request->user();

        try {
            if (!$user->stripe_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك اشتراك فعال لتحديث طريقة الدفع'
                ], 400);
            }

            $subscription = $user->subscription('default');

            if (!$subscription || !$subscription->valid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك اشتراك نشط لتحديث طريقة الدفع'
                ], 400);
            }

            $portalUrl = $user->billingPortalUrl(url('/'));

            return response()->json([
                'success' => true,
                'update_payment_url' => $portalUrl,
                'message' => 'استخدم الرابط لتحديث طريقة الدفع'
            ]);
        } catch (\Exception $e) {
            Log::error('Update payment method error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث طريقة الدفع: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على تاريخ الفواتير
     */
    public function invoices(Request $request)
    {
        $user = $request->user();

        try {
            if (!$user->stripe_id) {
                return response()->json([
                    'success' => true,
                    'invoices' => [],
                    'count' => 0,
                    'message' => 'ليس لديك فواتير مسجلة'
                ]);
            }

            $invoices = $user->invoices();

            $formattedInvoices = $invoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'date' => $invoice->date()->toDateString(),
                    'amount' => $invoice->total(),
                    'currency' => $invoice->currency,
                    'paid' => $invoice->paid,
                    'status' => $invoice->status,
                    'number' => $invoice->number,
                ];
            });

            return response()->json([
                'success' => true,
                'invoices' => $formattedInvoices,
                'count' => $formattedInvoices->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Invoices fetch error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الفواتير: ' . $e->getMessage()
            ], 500);
        }
    }
}
