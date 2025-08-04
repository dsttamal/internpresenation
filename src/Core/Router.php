<?php

namespace App\Core;

/**
 * Router Class
 * 
 * Handles URL routing and parameter matching for the API.
 */
class Router
{
    private array $routes = [];

    public function get(string $pattern, $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    public function post(string $pattern, $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    public function put(string $pattern, $handler): void
    {
        $this->addRoute('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, $handler): void
    {
        $this->addRoute('DELETE', $pattern, $handler);
    }

    public function patch(string $pattern, $handler): void
    {
        $this->addRoute('PATCH', $pattern, $handler);
    }

    public function options(string $pattern, $handler): void
    {
        $this->addRoute('OPTIONS', $pattern, $handler);
    }

    public function any(string $pattern, $handler): void
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
        foreach ($methods as $method) {
            $this->addRoute($method, $pattern, $handler);
        }
    }

    private function addRoute(string $method, string $pattern, $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    public function resolve(Request $request): ?array
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->matchRoute($route['pattern'], $uri);
            if ($params !== null) {
                return [
                    'handler' => $route['handler'],
                    'params' => $params
                ];
            }
        }

        return null;
    }

    private function matchRoute(string $pattern, string $uri): ?array
    {
        // Convert route pattern to regex
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            // Extract named parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return $params;
        }

        return null;
    }

    public function group(string $prefix, callable $callback): void
    {
        $originalRoutes = $this->routes;
        $this->routes = [];

        // Execute callback to register routes
        $callback($this);

        // Add prefix to all new routes
        foreach ($this->routes as &$route) {
            $route['pattern'] = $prefix . $route['pattern'];
        }

        // Merge with original routes
        $this->routes = array_merge($originalRoutes, $this->routes);
    }
}
