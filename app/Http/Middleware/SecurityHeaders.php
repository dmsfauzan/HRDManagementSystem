<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // CSP report-only: pantau pelanggaran tanpa memblokir (aman untuk Vite/Alpine).
        $csp = "default-src 'self'; "
            ."img-src 'self' data: https:; "
            ."font-src 'self' data: https://fonts.bunny.net; "
            ."style-src 'self' 'unsafe-inline' https://fonts.bunny.net; "
            ."script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
            ."connect-src 'self' ws: wss:; "
            ."frame-ancestors 'self'; base-uri 'self'; form-action 'self'";
        $response->headers->set('Content-Security-Policy-Report-Only', $csp);

        if ($request->secure() || config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        return $response;
    }
}
