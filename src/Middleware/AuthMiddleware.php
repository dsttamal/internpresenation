<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * Authentication Middleware
 * 
 * Validates JWT tokens and sets user context.
 */
class AuthMiddleware implements MiddlewareInterface
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function handle(Request $request, callable $next): Response
    {
        $token = $request->getBearerToken();

        if (!$token) {
            return Response::unauthorized('Token required');
        }

        try {
            $user = $this->authService->validateToken($token);
            
            // Add user to request context
            $request->setUser($user);
            
            return $next($request);
        } catch (\Exception $e) {
            return Response::unauthorized('Invalid token');
        }
    }
}
