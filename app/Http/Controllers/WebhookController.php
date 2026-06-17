<?php

namespace App\Http\Controllers;

use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends CashierController
{
    /**
     * معالجة جميع أحداث Stripe Webhook
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $eventType = $payload['type'] ?? 'unknown';

        Log::info('Stripe Webhook received: ' . $eventType);

        // تسجيل الحدث في قاعدة البيانات (اختياري)
        // يمكنك إنشاء جدول webhook_events لتخزين جميع الأحداث

        return parent::handleWebhook($request);
    }

    /**
     * التعامل مع حدث نجاح الدفع (اختياري)
     */
    protected function handleInvoicePaymentSucceeded($payload)
    {
        Log::info('Invoice payment succeeded: ' . json_encode($payload));

        // يمكنك إضافة منطق مخصص هنا
        // مثل: إرسال إشعار للعميل، تحديث حالة الطلب، إلخ

        return response()->json(['status' => 'success']);
    }

    /**
     * التعامل مع حدث فشل الدفع (اختياري)
     */
    protected function handleInvoicePaymentFailed($payload)
    {
        Log::warning('Invoice payment failed: ' . json_encode($payload));

        // يمكنك إضافة منطق مخصص هنا
        // مثل: إرسال إشعار للعميل لتحديث بطاقته

        return response()->json(['status' => 'success']);
    }

    /**
     * التعامل مع حدث إنشاء اشتراك جديد (اختياري)
     */
    protected function handleCustomerSubscriptionCreated($payload)
    {
        Log::info('Subscription created: ' . json_encode($payload));

        return response()->json(['status' => 'success']);
    }

    /**
     * التعامل مع حدث تحديث اشتراك (اختياري)
     */
    protected function handleCustomerSubscriptionUpdated($payload)
    {
        Log::info('Subscription updated: ' . json_encode($payload));

        return response()->json(['status' => 'success']);
    }

    /**
     * التعامل مع حدث إلغاء اشتراك (اختياري)
     */
    protected function handleCustomerSubscriptionDeleted($payload)
    {
        Log::info('Subscription deleted: ' . json_encode($payload));

        return response()->json(['status' => 'success']);
    }
}
