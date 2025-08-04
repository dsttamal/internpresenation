<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Error Handler Middleware
 * 
 * Catches and handles exceptions, providing consistent error responses.
 */
class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (\Exception $e) {
            return $this->handleException($e, $request);
        }
    }

    private function handleException(\Exception $e, Request $request): Response
    {
        // Log the error
        $this->logError($e, $request);

        // Determine response based on exception type
        $statusCode = $this->getStatusCode($e);
        $message = $this->getMessage($e);
        
        $response = [
            'success' => false,
            'message' => $message,
            'error' => get_class($e)
        ];

        // Add debug information in development
        if ($this->shouldShowDebugInfo()) {
            $response['debug'] = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        }

        return Response::json($response, $statusCode);
    }

    private function logError(\Exception $e, Request $request): void
    {
        $logMessage = sprintf(
            "[%s] %s: %s in %s:%d\nRequest: %s %s\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $request->getMethod(),
            $request->getUri(),
            $e->getTraceAsString()
        );

        error_log($logMessage);
    }

    private function getStatusCode(\Exception $e): int
    {
        // Map exception types to status codes
        if ($e instanceof \InvalidArgumentException) {
            return 400;
        }
        
        if ($e instanceof \UnauthorizedAccessException) {
            return 401;
        }
        
        if ($e instanceof \ForbiddenException) {
            return 403;
        }
        
        if ($e instanceof \NotFoundException) {
            return 404;
        }

        // Default to 500 for unknown exceptions
        return 500;
    }

    private function getMessage(\Exception $e): string
    {
        // Don't expose internal error messages in production
        if (!$this->shouldShowDebugInfo()) {
            return 'An error occurred while processing your request';
        }

        return $e->getMessage();
    }

    private function shouldShowDebugInfo(): bool
    {
        return ($_ENV['APP_DEBUG'] ?? false) && 
               ($_ENV['APP_ENV'] ?? 'production') === 'development';
    }
}
