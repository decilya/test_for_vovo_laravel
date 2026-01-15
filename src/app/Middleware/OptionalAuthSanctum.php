<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuthSanctum
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Attempt to authenticate only if a bearer token is provided
        if ($request->bearerToken()) {
            $user = Auth::guard('sanctum')->user();
            if (isset($user)) {
                Auth::login($user);
                $request->merge([
                    'optional_auth_user' => $user
                ]);
            }
        }

        return $next($request);
    }
}