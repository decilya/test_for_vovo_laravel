<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if (!$request->bearerToken()) {
            return $this->unauthorizedResponse('Authentication token is missing');
        }

        if (!Auth::guard('api')->check()) {
            return $this->unauthorizedResponse('Invalid or expired authentication token');
        }

        return $next($request);
    }

    /**
     * Return unauthorized response.
     *
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => $message,
            ],
            'timestamp' => now()->toIso8601String(),
            'docs' => url('/api/docs#authentication'),
        ], 401);
    }
}
