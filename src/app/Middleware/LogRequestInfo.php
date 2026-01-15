<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestInfo
{
    public function handle(Request $request, Closure $next)
    {
        $route = $request->path(); // или $request->url() — если нужен полный URL
        $ip = $request->ip();

        Log::channel('requestlog')->info('Request', [
            'route' => $route,
            'ip'    => $ip,
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ]);

        return $next($request);
    }
}
