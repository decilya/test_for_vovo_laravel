<?php

namespace App\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAPIToken
{

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);

        //  $authorized = false;
        // $token = $request->all()['token'];
       /* $token = $request->header('X-Token');
        if (!is_null($token)) {
            $authorized = User::checkAPIToken($token);
        }

        if ($authorized) {
            return $next($request);
        } else {
            return response(null, Response::HTTP_UNAUTHORIZED);
        } */
    }
}
