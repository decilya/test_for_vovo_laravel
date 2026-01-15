<?php

namespace App\Http\Middleware;

use App\Helpers\ErrorHelper;
use Closure;
use Illuminate\Support\Facades\Auth;

class Admin
{
    public function handle($request, Closure $next, $roles)
    {
        $envError = ErrorHelper::validateEnv();
        if ($envError !== null) {
            return response($envError, 500);
        }

        $user = Auth::user();
        if ($roles == 'admin') {
            // только администратор
            $roles_array = [3];
        } elseif ($roles == 'all') {
            // администратор и менеджер
            $roles_array = [3, 4];
        }
        if ($user && in_array($user->role_id, $roles_array)) {
            return $next($request);
        } else {
            return redirect('/login');
        }
    }
}
