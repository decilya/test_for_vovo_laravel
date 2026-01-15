<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CheckScopes
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$scopes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$scopes)
    {
        if (!Auth::check()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Authentication required',
            ], 401);
        }

        $user = Auth::user();

        // Для Passport токенов
        if (method_exists($user, 'token')) {
            $token = $user->token();

            if ($token) {
                $tokenScopes = $token->scopes ?? [];

                // Проверяем все required scopes
                foreach ($scopes as $scope) {
                    if (!in_array($scope, $tokenScopes) && !in_array('*', $tokenScopes)) {
                        return response()->json([
                            'error' => 'insufficient_scope',
                            'message' => "Missing required scope: {$scope}",
                            'required_scopes' => $scopes,
                        ], 403);
                    }
                }
            }
        }

        return $next($request);
    }
}
