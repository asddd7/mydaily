<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustLocalNgrokProxies
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            $request->setTrustedProxies(
                [$request->server->get('REMOTE_ADDR')],
                Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO,
            );
        }

        return $next($request);
    }
}
