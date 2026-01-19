<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{

    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        if (empty($guards)) {
            $guards = ['api'];
        }

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Пользователь аутентифицирован
                Auth::shouldUse($guard);
                return $next($request);
            }
        }

        // Пользователь не аутентифицирован
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please authenticate.'
        ], 401);
    }
}
