<?php

namespace App\Core;

/**
 * HTTP Response Handler
 * 
 * Handles HTTP responses and provides convenient
 * methods to send JSON responses with proper headers.
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private $body = '';

    public function __construct(int $statusCode = 200, array $headers = [], $body = '')
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    public static function json(array $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json';
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        return new self($statusCode, $headers, $body);
    }

    public static function success(array $data = [], string $message = 'Success', int $statusCode = 200): self
    {
        return self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public static function error(string $message, int $statusCode = 400, array $errors = []): self
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return self::json($response, $statusCode);
    }

    public static function notFound(string $message = 'Resource not found'): self
    {
        return self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return self::error($message, 403);
    }

    public static function serverError(string $message = 'Internal Server Error'): self
    {
        return self::error($message, 500);
    }

    public static function created(array $data = [], string $message = 'Created successfully'): self
    {
        return self::success($data, $message, 201);
    }

    public static function noContent(): self
    {
        return new self(204);
    }

    public static function html(string $html, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new self($statusCode, $headers, $html);
    }

    public static function make($body, int $statusCode = 200, array $headers = []): self
    {
        return new self($statusCode, $headers, $body);
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function setBody($body): self
    {
        $this->body = $body;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function send(): void
    {
        // Set status code
        http_response_code($this->statusCode);

        // Set headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send body
        echo $this->body;
    }

    public function withCors(array $allowedOrigins = ['*'], array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']): self
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            $this->setHeader('Access-Control-Allow-Origin', $origin ?: '*');
        }
        
        $this->setHeader('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
        $this->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, Cache-Control, Pragma');
        $this->setHeader('Access-Control-Allow-Credentials', 'true');
        $this->setHeader('Access-Control-Max-Age', '86400');

        return $this;
    }
}
