<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Redis;

class ThrottleRequests
{
    public function handle($request, Closure $next, $maxAttempts = 10, $decayMinutes = 1)
    {
        $key = 'throttle:' . $request->ip();
        $attempts = Redis::get($key);

        if ($attempts >= $maxAttempts) {
            return response()->json([
                'error' => 'Too many requests. Please try again later.'
            ], 429);
        }

        Redis::set($key, ($attempts ?? 0) + 1);
        Redis::expire($key, $decayMinutes * 60);

        return $next($request);
    }
}
