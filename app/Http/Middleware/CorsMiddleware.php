<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', '*'));
        $origin = $request->headers->get('Origin');
        $allowOrigin = in_array('*', $allowedOrigins) ? '*' : (in_array($origin, $allowedOrigins) ? $origin : '');

        if ($allowOrigin !== '') {
            $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
            $response->headers->set('Vary', 'Origin');
        }

        $response->headers->set('Access-Control-Allow-Methods', env('CORS_ALLOWED_METHODS', 'GET, POST, PUT, PATCH, DELETE, OPTIONS'));
        $response->headers->set('Access-Control-Allow-Headers', env('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-Requested-With'));
        $response->headers->set('Access-Control-Allow-Credentials', env('CORS_ALLOW_CREDENTIALS', 'true'));
        $response->headers->set('Access-Control-Max-Age', env('CORS_MAX_AGE', '86400'));

        if ($request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(204);
        }

        return $response;
    }
}
