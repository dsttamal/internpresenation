<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Security Middleware
 * 
 * Adds security headers to protect against common attacks.
 */
class SecurityMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        // Add security headers
        $response->setHeaders([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => $this->getContentSecurityPolicy(),
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        ]);

        return $response;
    }

    private function getContentSecurityPolicy(): string
    {
        return "default-src 'self'; " .
               "style-src 'self' 'unsafe-inline'; " .
               "script-src 'self'; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self'; " .
               "font-src 'self'; " .
               "object-src 'none'; " .
               "media-src 'self'; " .
               "frame-src 'none';";
    }
}
