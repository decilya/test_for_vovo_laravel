<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CheckClientCredentials
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$scopes
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$scopes)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Missing authentication token',
            ], 401);
        }

        $tokenModel = \Laravel\Passport\Token::where('id', $token)
            ->where('revoked', false)
            ->first();

        if (!$tokenModel) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'Invalid or expired token',
            ], 401);
        }

        if (!empty($scopes)) {
            $tokenScopes = $tokenModel->scopes ?? [];

            if (!in_array('*', $tokenScopes)) {
                foreach ($scopes as $scope) {
                    if (!in_array($scope, $tokenScopes)) {
                        return response()->json([
                            'error' => 'insufficient_scope',
                            'message' => "Missing required scope: {$scope}",
                            'required_scopes' => $scopes,
                        ], 403);
                    }
                }
            }
        }

        $user = \App\Models\User::find($tokenModel->user_id);
        if ($user) {
            Auth::setUser($user);
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
        }

        return $next($request);
    }
}
