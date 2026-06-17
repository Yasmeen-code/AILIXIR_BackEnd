<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Subscribed
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->subscribed('default')) {
            return response()->json([
                'success' => false,
                'message' => 'هذه الميزة متاحة للمشتركين فقط. يرجى الاشتراك أولاً.'
            ], 403);
        }

        $subscription = $user->subscription('default');

        if ($subscription->ended()) {
            return response()->json([
                'success' => false,
                'message' => 'انتهى اشتراكك. يرجى تجديده للاستمرار في استخدام الخدمة.'
            ], 403);
        }

        if ($subscription->pastDue()) {
            return response()->json([
                'success' => false,
                'message' => 'فشل تحصيل مبلغ الاشتراك. يرجى تحديث طريقة الدفع.'
            ], 403);
        }

        return $next($request);
    }
}
