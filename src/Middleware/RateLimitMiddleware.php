<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Rate Limiting Middleware
 * 
 * Implements rate limiting to prevent API abuse.
 * Uses file-based storage for simplicity (consider Redis for production).
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxRequests;
    private int $windowSeconds;
    private string $storageDir;

    public function __construct()
    {
        $this->maxRequests = (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? 1000);
        $this->windowSeconds = (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 900); // 15 minutes
        $this->storageDir = __DIR__ . '/../../storage/rate_limits';
        
        // Create storage directory if it doesn't exist
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    public function handle(Request $request, callable $next): Response
    {
        $clientId = $this->getClientId($request);
        
        // Skip rate limiting for certain endpoints in development
        if ($this->shouldSkipRateLimit($request)) {
            return $next($request);
        }

        if (!$this->isAllowed($clientId)) {
            return Response::error('Too many requests. Please try again later.', 429);
        }

        $response = $next($request);
        
        // Add rate limit headers
        $stats = $this->getClientStats($clientId);
        $response->setHeaders([
            'X-RateLimit-Limit' => (string) $this->maxRequests,
            'X-RateLimit-Remaining' => (string) max(0, $this->maxRequests - $stats['count']),
            'X-RateLimit-Reset' => (string) ($stats['window_start'] + $this->windowSeconds),
        ]);

        return $response;
    }

    private function getClientId(Request $request): string
    {
        // Use IP address as client identifier
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private function shouldSkipRateLimit(Request $request): bool
    {
        $isDevelopment = ($_ENV['APP_ENV'] ?? 'production') === 'development';
        $isTestEndpoint = strpos($request->getUri(), '/api/test') !== false ||
                         strpos($request->getUri(), '/api/health') !== false;
        
        return $isDevelopment && $isTestEndpoint;
    }

    private function isAllowed(string $clientId): bool
    {
        $stats = $this->getClientStats($clientId);
        $now = time();

        // Check if we're in a new window
        if ($now >= $stats['window_start'] + $this->windowSeconds) {
            // Reset counter for new window
            $this->resetClientStats($clientId, $now);
            return true;
        }

        // Check if limit exceeded
        if ($stats['count'] >= $this->maxRequests) {
            return false;
        }

        // Increment counter
        $this->incrementClientStats($clientId);
        return true;
    }

    private function getClientStats(string $clientId): array
    {
        $filePath = $this->getClientFile($clientId);
        
        if (!file_exists($filePath)) {
            return [
                'count' => 0,
                'window_start' => time()
            ];
        }

        $data = json_decode(file_get_contents($filePath), true);
        return $data ?: [
            'count' => 0,
            'window_start' => time()
        ];
    }

    private function resetClientStats(string $clientId, int $windowStart): void
    {
        $filePath = $this->getClientFile($clientId);
        file_put_contents($filePath, json_encode([
            'count' => 1,
            'window_start' => $windowStart
        ]));
    }

    private function incrementClientStats(string $clientId): void
    {
        $stats = $this->getClientStats($clientId);
        $stats['count']++;
        
        $filePath = $this->getClientFile($clientId);
        file_put_contents($filePath, json_encode($stats));
    }

    private function getClientFile(string $clientId): string
    {
        $safeId = preg_replace('/[^a-zA-Z0-9._-]/', '_', $clientId);
        return $this->storageDir . '/' . $safeId . '.json';
    }
}
