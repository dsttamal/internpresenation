<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * CORS Middleware
 * 
 * Handles Cross-Origin Resource Sharing (CORS) headers
 * to allow frontend applications to access the API.
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Handle preflight OPTIONS requests
        if ($request->isOptions()) {
            return $this->handlePreflight($request);
        }

        // Process request and add CORS headers to response
        $response = $next($request);
        return $this->addCorsHeaders($response, $request);
    }

    private function handlePreflight(Request $request): Response
    {
        $response = Response::noContent();
        return $this->addCorsHeaders($response, $request);
    }

    private function addCorsHeaders(Response $response, Request $request): Response
    {
        $allowedOrigins = $this->getAllowedOrigins();
        $origin = $request->getHeader('origin') ?? '';

        // Check if origin is allowed
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin ?: '*');
        }

        $response->setHeaders([
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, Cache-Control, Pragma, X-Requested-With',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '86400', // 24 hours
        ]);

        return $response;
    }

    private function getAllowedOrigins(): array
    {
        $origins = $_ENV['ALLOWED_ORIGINS'] ?? $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000';
        return array_map('trim', explode(',', $origins));
    }
}
