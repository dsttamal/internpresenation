<?php

namespace App\Core;

use App\Middleware\MiddlewareInterface;

/**
 * Application Class
 * 
 * Main application container that handles middleware
 * and request processing.
 */
class Application
{
    private array $middleware = [];

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middleware[] = $middleware;
    }

    public function handle(Request $request, Router $router): Response
    {
        // Create middleware chain
        $next = function(Request $request) use ($router) {
            return $this->handleRoute($request, $router);
        };

        // Execute middleware in reverse order
        for ($i = count($this->middleware) - 1; $i >= 0; $i--) {
            $middleware = $this->middleware[$i];
            $next = function(Request $request) use ($middleware, $next) {
                return $middleware->handle($request, $next);
            };
        }

        return $next($request);
    }

    private function handleRoute(Request $request, Router $router): Response
    {
        $route = $router->resolve($request);

        if ($route === null) {
            return Response::notFound('Route not found');
        }

        // Set route parameters in request
        $request->setParams($route['params']);

        $handler = $route['handler'];

        try {
            if (is_callable($handler)) {
                return $handler($request);
            }

            if (is_array($handler) && count($handler) === 2) {
                [$controller, $method] = $handler;
                
                if (is_string($controller)) {
                    $controller = new $controller();
                }

                if (method_exists($controller, $method)) {
                    return $controller->$method($request);
                }
            }

            return Response::serverError('Invalid route handler');

        } catch (\Exception $e) {
            // Log error
            error_log("Route handler error: " . $e->getMessage());
            
            return Response::serverError('An error occurred processing your request');
        }
    }
}
