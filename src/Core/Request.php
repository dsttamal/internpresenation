<?php

namespace App\Core;

/**
 * HTTP Request Handler
 * 
 * Handles all incoming HTTP requests and provides
 * convenient methods to access request data.
 */
class Request
{
    private array $query;
    private array $body;
    private array $headers;
    private array $files;
    private string $method;
    private string $uri;
    private array $params = [];
    private $user = null;

    public function __construct(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        array $headers = [],
        array $files = []
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->query = $query;
        $this->body = $body;
        $this->headers = $headers;
        $this->files = $files;
    }

    /**
     * Create request from PHP globals
     */
    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        
        // Get query parameters
        $query = $_GET ?? [];
        
        // Get request body
        $body = [];
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                $input = file_get_contents('php://input');
                $body = json_decode($input, true) ?? [];
            } else {
                $body = $_POST ?? [];
            }
        }
        
        // Get headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(['HTTP_', '_'], ['', '-'], $key);
                $headers[strtolower($header)] = $value;
            }
        }
        
        // Add authorization header if present
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (function_exists('apache_request_headers')) {
            $apacheHeaders = apache_request_headers();
            if (isset($apacheHeaders['Authorization'])) {
                $headers['authorization'] = $apacheHeaders['Authorization'];
            }
        }
        
        return new self($method, $uri, $query, $body, $headers, $_FILES ?? []);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getQuery(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function getBody(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }

    public function getHeader(string $key): ?string
    {
        return $this->headers[strtolower($key)] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function getFile(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getBearerToken(): ?string
    {
        $authorization = $this->getHeader('authorization');
        if ($authorization && strpos($authorization, 'Bearer ') === 0) {
            return substr($authorization, 7);
        }
        return null;
    }

    public function isJson(): bool
    {
        $contentType = $this->getHeader('content-type') ?? '';
        return strpos($contentType, 'application/json') !== false;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPut(): bool
    {
        return $this->method === 'PUT';
    }

    public function isDelete(): bool
    {
        return $this->method === 'DELETE';
    }

    public function isPatch(): bool
    {
        return $this->method === 'PATCH';
    }

    public function isOptions(): bool
    {
        return $this->method === 'OPTIONS';
    }

    public function setUser($user): void
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
