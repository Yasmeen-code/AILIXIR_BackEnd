<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProPlan
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user->isPro() && !$user->isMax()) {

            return response()->json([
                'message' => 'Pro plan required'
            ], 403);
        }

        return $next($request);
    }
}
