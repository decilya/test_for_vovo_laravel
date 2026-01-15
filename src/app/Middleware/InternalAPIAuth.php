<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Service\InterServiceEncryption;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InternalAPIAuth
{
    public function __construct(protected InterServiceEncryption $interServiceEncryption)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role  = null): Response
    {
        $pass = false;

        $internalAuth = $request->header('X-Internal-Auth');

        if ($internalAuth !== null) {
            $decoded  = $this->interServiceEncryption->decrypt($internalAuth);
            if ($decoded !== null) {
                $pass = empty($role);
                $userId = $decoded['user_id'] ?? null;
                $user = User::find($userId);
                if ($user) {
                    Auth::login($user);
                    $pass = true;
                }
            }
        }

        if ($pass) {
            return $next($request);
        } else {
            return response(null, Response::HTTP_UNAUTHORIZED);
        }
    }
}