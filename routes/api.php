<?php

/**
 * API Routes
 * 
 * Define all API endpoints and their corresponding controllers.
 */

use App\Controllers\AuthController;
use App\Controllers\FormController;
use App\Controllers\SubmissionController;
use App\Controllers\AdminController;
use App\Controllers\PaymentController;
use App\Controllers\ExportController;
use App\Controllers\SimpleSwaggerController;
use App\Core\Response;

// ================================
// Documentation Routes
// ================================

// Swagger UI
$router->get('/api/docs', [SimpleSwaggerController::class, 'ui']);

// OpenAPI Specification
$router->get('/api/docs/json', [SimpleSwaggerController::class, 'json']);
$router->get('/api/docs/yaml', [SimpleSwaggerController::class, 'yaml']);

// Test HTML response
$router->get('/api/test-html', function() {
    return Response::html('<h1>HTML Response Test</h1><p>If you can see this, HTML responses are working!</p>');
});

// Debug Swagger endpoint
$router->get('/api/debug-swagger', function() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:5000';
    $baseUrl = $protocol . '://' . $host;
    
    return Response::json([
        'swagger_endpoints' => [
            'ui' => $baseUrl . '/api/docs',
            'json' => $baseUrl . '/api/docs/json',
            'yaml' => $baseUrl . '/api/docs/yaml'
        ],
        'server_info' => [
            'protocol' => $protocol,
            'host' => $host,
            'base_url' => $baseUrl,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ],
        'test_json_endpoint' => 'Try: ' . $baseUrl . '/api/docs/json',
        'test_ui_endpoint' => 'Try: ' . $baseUrl . '/api/docs'
    ]);
});

// ================================
// Health & Status Routes
// ================================

/**
 * @OA\Get(
 *     path="/api/health",
 *     tags={"Health"},
 *     summary="Health check",
 *     description="Check if the API server is running and healthy",
 *     @OA\Response(
 *         response=200,
 *         description="Server is healthy",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="status", type="string", example="OK"),
 *             @OA\Property(property="message", type="string", example="Server is running"),
 *             @OA\Property(property="timestamp", type="string", example="2024-01-20 10:30:00"),
 *             @OA\Property(property="version", type="string", example="1.0.0")
 *         )
 *     )
 * )
 */
$router->get('/api/health', function() {
    return Response::success([
        'status' => 'OK',
        'message' => 'Server is running',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0'
    ]);
});

/**
 * @OA\Get(
 *     path="/api/test-cors",
 *     tags={"Health"},
 *     summary="Test CORS",
 *     description="Test Cross-Origin Resource Sharing (CORS) configuration",
 *     @OA\Response(
 *         response=200,
 *         description="CORS is working",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="CORS is working!"),
 *             @OA\Property(property="timestamp", type="string")
 *         )
 *     )
 * )
 */
$router->get('/api/test-cors', function() {
    return Response::success([
        'message' => 'CORS is working!',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Rate limit status
$router->get('/api/rate-limit-status', function() {
    return Response::success([
        'message' => 'Rate limit status',
        'limit' => $_ENV['RATE_LIMIT_REQUESTS'] ?? 1000,
        'window' => ($_ENV['RATE_LIMIT_WINDOW'] ?? 900) . ' seconds',
        'environment' => $_ENV['APP_ENV'] ?? 'development'
    ]);
});

// Authentication routes
$router->group('/api/auth', function($router) {
    $router->post('/register', [AuthController::class, 'register']);
    $router->post('/login', [AuthController::class, 'login']);
    $router->get('/profile', [AuthController::class, 'profile']);
    $router->post('/refresh', [AuthController::class, 'refresh']);
    $router->post('/change-password', [AuthController::class, 'changePassword']);
    $router->post('/logout', [AuthController::class, 'logout']);
});

// Form routes
$router->group('/api/forms', function($router) {
    // Public routes
    $router->get('/public/{customUrl}', [FormController::class, 'showByUrl']);
    
    // Protected routes (require authentication)
    $router->get('', [FormController::class, 'index']);
    $router->post('', [FormController::class, 'store']);
    $router->get('/my', [FormController::class, 'myForms']);
    $router->get('/{id}', [FormController::class, 'show']);
    $router->put('/{id}', [FormController::class, 'update']);
    $router->delete('/{id}', [FormController::class, 'destroy']);
    $router->post('/{id}/duplicate', [FormController::class, 'duplicate']);
    $router->get('/{id}/analytics', [FormController::class, 'analytics']);
    $router->patch('/{id}/toggle-status', [FormController::class, 'toggleStatus']);
});

// Submission routes
$router->group('/api/submissions', function($router) {
    // Public routes
    $router->post('', [SubmissionController::class, 'store']);
    $router->get('/public/{uniqueId}', [SubmissionController::class, 'showPublic']);
    $router->put('/public/{uniqueId}', [SubmissionController::class, 'updatePublic']);
    $router->post('/public/{uniqueId}/verify-edit-code', [SubmissionController::class, 'verifyEditCode']);
    
    // Protected routes
    $router->get('', [SubmissionController::class, 'index']);
    $router->get('/{id}', [SubmissionController::class, 'show']);
    $router->put('/{id}', [SubmissionController::class, 'update']);
    $router->delete('/{id}', [SubmissionController::class, 'destroy']);
    $router->patch('/{id}/status', [SubmissionController::class, 'updateStatus']);
    $router->get('/form/{formId}', [SubmissionController::class, 'getByForm']);
});

// Payment routes
$router->group('/api/payment', function($router) {
    // Stripe routes
    $router->post('/stripe/create-intent', [PaymentController::class, 'createStripePaymentIntent']);
    $router->post('/stripe/confirm', [PaymentController::class, 'confirmStripePayment']);
    $router->post('/stripe/webhook', [PaymentController::class, 'stripeWebhook']);
    
    // Bank transfer routes
    $router->post('/bank-transfer', [PaymentController::class, 'bankTransfer']);
    $router->patch('/bank-transfer/{id}/approve', [PaymentController::class, 'approveBankTransfer']);
    $router->patch('/bank-transfer/{id}/reject', [PaymentController::class, 'rejectBankTransfer']);
});

// bKash payment routes
$router->group('/api/bkash', function($router) {
    $router->post('/create', [PaymentController::class, 'createBkashPayment']);
    $router->post('/execute', [PaymentController::class, 'executeBkashPayment']);
    $router->post('/query', [PaymentController::class, 'queryBkashPayment']);
    $router->post('/refund', [PaymentController::class, 'refundBkashPayment']);
});

// Admin routes
$router->group('/api/admin', function($router) {
    // User management
    $router->get('/users', [AdminController::class, 'getUsers']);
    $router->post('/users', [AdminController::class, 'createUser']);
    $router->get('/users/{id}', [AdminController::class, 'getUser']);
    $router->put('/users/{id}', [AdminController::class, 'updateUser']);
    $router->delete('/users/{id}', [AdminController::class, 'deleteUser']);
    $router->patch('/users/{id}/toggle-status', [AdminController::class, 'toggleUserStatus']);
    $router->post('/users/{id}/reset-password', [AdminController::class, 'resetUserPassword']);
    
    // System settings
    $router->get('/settings', [AdminController::class, 'getSettings']);
    $router->put('/settings', [AdminController::class, 'updateSettings']);
    
    // System stats
    $router->get('/stats', [AdminController::class, 'getSystemStats']);
    $router->get('/dashboard', [AdminController::class, 'getDashboardData']);
});

// Export routes
$router->group('/api/export', function($router) {
    $router->post('/csv', [ExportController::class, 'exportCsv']);
    $router->post('/pdf', [ExportController::class, 'exportPdf']);
    $router->get('/download/{filename}', [ExportController::class, 'downloadFile']);
});

// File upload routes
$router->group('/api/upload', function($router) {
    $router->post('/payment-receipt', [PaymentController::class, 'uploadPaymentReceipt']);
    $router->get('/files/{filename}', [PaymentController::class, 'getUploadedFile']);
});

// Settings routes
$router->group('/api/settings', function($router) {
    $router->get('', [AdminController::class, 'getPublicSettings']);
    $router->get('/payment-methods', [PaymentController::class, 'getPaymentMethods']);
});

// Handle 404 for API routes
$router->any('/api/{path:.*}', function() {
    return Response::notFound('API endpoint not found');
});

// Serve uploaded files (if not using a web server for static files)
$router->get('/uploads/{filename}', function($request) {
    $filename = $request->getParam('filename');
    $filepath = __DIR__ . '/../uploads/' . $filename;
    
    if (!file_exists($filepath)) {
        return Response::notFound('File not found');
    }
    
    // Security check - ensure file is in uploads directory
    $realPath = realpath($filepath);
    $uploadsPath = realpath(__DIR__ . '/../uploads/');
    
    if (strpos($realPath, $uploadsPath) !== 0) {
        return Response::forbidden('Access denied');
    }
    
    // Serve file with appropriate headers
    $mimeType = mime_content_type($filepath);
    $response = new Response(200, [
        'Content-Type' => $mimeType,
        'Content-Length' => filesize($filepath),
        'Cache-Control' => 'public, max-age=31536000'
    ], file_get_contents($filepath));
    
    return $response;
});
