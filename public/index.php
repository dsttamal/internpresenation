<?php
/**
 * Form Builder API - Entry Point
 * 
 * This is the main entry point for the PHP backend API.
 * It handles all incoming HTTP requests and routes them appropriately.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use App\Core\Router;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\CorsMiddleware;
use App\Middleware\SecurityMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\ErrorHandlerMiddleware;
use Config\Database;
use Dotenv\Dotenv;

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();

    // Initialize database connection (with fallback)
    try {
        Database::initialize();
    } catch (\Exception $e) {
        // Log the error but continue - some endpoints may work without database
        error_log('Database initialization warning: ' . $e->getMessage());
        // For documentation endpoints, we can continue without full database
    }

    // Initialize application
    $app = new Application();

    // Register global middleware (order matters)
    $app->addMiddleware(new ErrorHandlerMiddleware());
    $app->addMiddleware(new SecurityMiddleware());
    $app->addMiddleware(new CorsMiddleware());
    $app->addMiddleware(new RateLimitMiddleware());

    // Create router and register routes
    $router = new Router();
    require_once __DIR__ . '/../routes/api.php';

    // Handle the request
    $request = Request::createFromGlobals();
    $response = $app->handle($request, $router);
    
    // Send response
    $response->send();

} catch (Exception $e) {
    // Emergency error handling
    http_response_code(500);
    header('Content-Type: application/json');
    
    $errorResponse = [
        'error' => 'Internal Server Error',
        'message' => 'An unexpected error occurred'
    ];
    
    // Show detailed error in development
    if (($_ENV['APP_DEBUG'] ?? false) && $_ENV['APP_ENV'] === 'development') {
        $errorResponse['debug'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    echo json_encode($errorResponse, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
