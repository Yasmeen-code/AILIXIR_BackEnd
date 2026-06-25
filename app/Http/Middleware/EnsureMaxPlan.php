<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMaxPlan
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user->isMax()) {
            return response()->json([
                'message' => 'Max plan required'
            ], 403);
        }
        return $next($request);
    }
}
