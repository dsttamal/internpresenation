<?php

namespace App\Controllers;

use App\Core\Response;

/**
 * @OA\Tag(
 *     name="Documentation",
 *     description="API Documentation endpoints"
 * )
 */
class SimpleSwaggerController
{
    public function ui()
    {
        $html = $this->getSwaggerUI();
        return Response::html($html);
    }

    public function json()
    {
        $openapi = $this->getBasicOpenApiSpec();
        return Response::json($openapi);
    }

    public function yaml()
    {
        $openapi = $this->getBasicOpenApiSpec();
        // Simple YAML conversion (basic implementation)
        $yaml = $this->arrayToYaml($openapi);
        
        return Response::make($yaml, 200, [
            'Content-Type' => 'application/x-yaml'
        ]);
    }

    private function getBasicOpenApiSpec()
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Form Builder API',
                'version' => '1.0.0',
                'description' => 'A comprehensive API for building, managing, and processing dynamic forms with payment integration',
                'contact' => [
                    'email' => 'admin@bsmmupathalumni.org',
                    'name' => 'API Support'
                ],
                'license' => [
                    'name' => 'MIT',
                    'url' => 'https://opensource.org/licenses/MIT'
                ]
            ],
            'servers' => [
                [
                    'url' => 'http://localhost:5000',
                    'description' => 'Development server'
                ],
                [
                    'url' => 'https://api.bsmmupathalumni.org',
                    'description' => 'Production server'
                ]
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'JWT Authorization header using the Bearer scheme'
                    ]
                ],
                'schemas' => [
                    'User' => [
                        'type' => 'object',
                        'required' => ['id', 'name', 'email', 'role'],
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'name' => ['type' => 'string', 'example' => 'John Doe'],
                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                            'role' => ['type' => 'string', 'enum' => ['user', 'admin', 'super_admin'], 'example' => 'user'],
                            'isActive' => ['type' => 'boolean', 'example' => true],
                            'createdAt' => ['type' => 'string', 'format' => 'date-time'],
                            'updatedAt' => ['type' => 'string', 'format' => 'date-time']
                        ]
                    ],
                    'Form' => [
                        'type' => 'object',
                        'required' => ['id', 'title', 'fields'],
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'title' => ['type' => 'string', 'example' => 'Event Registration'],
                            'description' => ['type' => 'string', 'example' => 'Registration form for annual event'],
                            'fields' => ['type' => 'array', 'items' => ['type' => 'object']],
                            'isActive' => ['type' => 'boolean', 'example' => true],
                            'allowEditing' => ['type' => 'boolean', 'example' => true],
                            'customUrl' => ['type' => 'string', 'example' => 'event-registration'],
                            'createdBy' => ['type' => 'integer', 'example' => 1],
                            'submissionCount' => ['type' => 'integer', 'example' => 42],
                            'createdAt' => ['type' => 'string', 'format' => 'date-time'],
                            'updatedAt' => ['type' => 'string', 'format' => 'date-time']
                        ]
                    ],
                    'Submission' => [
                        'type' => 'object',
                        'required' => ['id', 'formId', 'data'],
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'uniqueId' => ['type' => 'string', 'example' => 'sub_abc123def456'],
                            'formId' => ['type' => 'integer', 'example' => 1],
                            'data' => ['type' => 'object'],
                            'status' => ['type' => 'string', 'enum' => ['pending', 'completed', 'failed'], 'example' => 'pending'],
                            'paymentMethod' => ['type' => 'string', 'example' => 'stripe'],
                            'submitterInfo' => ['type' => 'object'],
                            'paymentInfo' => ['type' => 'object'],
                            'createdAt' => ['type' => 'string', 'format' => 'date-time'],
                            'updatedAt' => ['type' => 'string', 'format' => 'date-time']
                        ]
                    ],
                    'Payment' => [
                        'type' => 'object',
                        'required' => ['id', 'submissionId', 'amount', 'currency'],
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'submissionId' => ['type' => 'integer', 'example' => 123],
                            'paymentMethod' => ['type' => 'string', 'example' => 'stripe'],
                            'paymentId' => ['type' => 'string', 'example' => 'pi_1234567890'],
                            'amount' => ['type' => 'number', 'format' => 'float', 'example' => 99.99],
                            'currency' => ['type' => 'string', 'example' => 'USD'],
                            'status' => ['type' => 'string', 'enum' => ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'], 'example' => 'completed'],
                            'metadata' => ['type' => 'object'],
                            'receiptUrl' => ['type' => 'string', 'example' => 'https://example.com/receipt/123'],
                            'refundedAmount' => ['type' => 'number', 'format' => 'float', 'example' => 0.00],
                            'createdAt' => ['type' => 'string', 'format' => 'date-time'],
                            'updatedAt' => ['type' => 'string', 'format' => 'date-time']
                        ]
                    ],
                    'Settings' => [
                        'type' => 'object',
                        'required' => ['id', 'key', 'value'],
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'key' => ['type' => 'string', 'example' => 'site_name'],
                            'value' => ['type' => 'string', 'example' => 'Form Builder System'],
                            'type' => ['type' => 'string', 'enum' => ['string', 'boolean', 'integer', 'float', 'json'], 'example' => 'string'],
                            'description' => ['type' => 'string', 'example' => 'The name of the website'],
                            'category' => ['type' => 'string', 'example' => 'general'],
                            'isPublic' => ['type' => 'boolean', 'example' => false],
                            'createdAt' => ['type' => 'string', 'format' => 'date-time'],
                            'updatedAt' => ['type' => 'string', 'format' => 'date-time']
                        ]
                    ],
                    'ApiResponse' => [
                        'type' => 'object',
                        'required' => ['success', 'message'],
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'message' => ['type' => 'string'],
                            'data' => ['type' => 'object', 'nullable' => true]
                        ]
                    ]
                ]
            ],
            'tags' => [
                ['name' => 'Health', 'description' => 'System health checks'],
                ['name' => 'Authentication', 'description' => 'User authentication and authorization'],
                ['name' => 'Forms', 'description' => 'Form management and configuration'],
                ['name' => 'Submissions', 'description' => 'Form submission handling'],
                ['name' => 'Payments', 'description' => 'Payment processing and management'],
                ['name' => 'Admin', 'description' => 'Administrative operations'],
                ['name' => 'Export', 'description' => 'Data export operations'],
                ['name' => 'Settings', 'description' => 'System configuration'],
                ['name' => 'Documentation', 'description' => 'API documentation']
            ],
            'paths' => [
                '/api/health' => [
                    'get' => [
                        'tags' => ['Health'],
                        'summary' => 'Health check',
                        'description' => 'Check if the API server is running and healthy',
                        'responses' => [
                            '200' => [
                                'description' => 'Server is healthy',
                                'content' => [
                                    'application/json' => [
                                        'schema' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'success' => ['type' => 'boolean', 'example' => true],
                                                'status' => ['type' => 'string', 'example' => 'OK'],
                                                'message' => ['type' => 'string', 'example' => 'Server is running'],
                                                'timestamp' => ['type' => 'string', 'example' => '2024-01-20 10:30:00'],
                                                'version' => ['type' => 'string', 'example' => '1.0.0']
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                '/api/auth/register' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'summary' => 'Register a new user',
                        'description' => 'Create a new user account with email and password',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['name', 'email', 'password'],
                                        'properties' => [
                                            'name' => ['type' => 'string', 'example' => 'John Doe'],
                                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                                            'password' => ['type' => 'string', 'format' => 'password', 'minLength' => 6]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => [
                                'description' => 'User registered successfully',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ApiResponse']
                                    ]
                                ]
                            ],
                            '400' => ['description' => 'Validation error'],
                            '409' => ['description' => 'Email already exists']
                        ]
                    ]
                ],
                '/api/auth/login' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'summary' => 'User login',
                        'description' => 'Authenticate user with email and password',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['email', 'password'],
                                        'properties' => [
                                            'email' => ['type' => 'string', 'example' => 'john@example.com'],
                                            'password' => ['type' => 'string', 'format' => 'password']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Login successful',
                                'content' => [
                                    'application/json' => [
                                        'schema' => ['$ref' => '#/components/schemas/ApiResponse']
                                    ]
                                ]
                            ],
                            '401' => ['description' => 'Invalid credentials']
                        ]
                    ]
                ],
                '/api/forms' => [
                    'get' => [
                        'tags' => ['Forms'],
                        'summary' => 'Get all forms',
                        'description' => 'Retrieve a paginated list of forms',
                        'security' => [['bearerAuth' => []]],
                        'parameters' => [
                            [
                                'name' => 'page',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1]
                            ],
                            [
                                'name' => 'limit',
                                'in' => 'query',
                                'required' => false,
                                'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20]
                            ]
                        ],
                        'responses' => [
                            '200' => ['description' => 'Forms retrieved successfully'],
                            '401' => ['description' => 'Unauthorized']
                        ]
                    ],
                    'post' => [
                        'tags' => ['Forms'],
                        'summary' => 'Create a new form',
                        'description' => 'Create a new form with specified fields',
                        'security' => [['bearerAuth' => []]],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['title', 'fields'],
                                        'properties' => [
                                            'title' => ['type' => 'string', 'example' => 'Contact Form'],
                                            'description' => ['type' => 'string', 'nullable' => true],
                                            'fields' => [
                                                'type' => 'array',
                                                'items' => [
                                                    'type' => 'object',
                                                    'properties' => [
                                                        'type' => ['type' => 'string', 'example' => 'text'],
                                                        'name' => ['type' => 'string', 'example' => 'full_name'],
                                                        'label' => ['type' => 'string', 'example' => 'Full Name'],
                                                        'required' => ['type' => 'boolean', 'example' => true]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'responses' => [
                            '201' => ['description' => 'Form created successfully'],
                            '400' => ['description' => 'Validation error'],
                            '401' => ['description' => 'Unauthorized']
                        ]
                    ]
                ]
            ]
        ];
    }

    private function arrayToYaml($array, $indent = 0)
    {
        $yaml = '';
        foreach ($array as $key => $value) {
            $yaml .= str_repeat('  ', $indent) . $key . ':';
            if (is_array($value)) {
                $yaml .= "\n" . $this->arrayToYaml($value, $indent + 1);
            } else {
                $yaml .= ' ' . (is_string($value) ? '"' . $value . '"' : $value) . "\n";
            }
        }
        return $yaml;
    }

    private function getSwaggerUI()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:5000';
        $baseUrl = $protocol . '://' . $host;
        
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Builder API Documentation</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1976d2, #1565c0);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        .content {
            padding: 40px;
        }
        .section {
            margin-bottom: 40px;
        }
        .section h2 {
            color: #1976d2;
            border-bottom: 2px solid #e3f2fd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .endpoint-group {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .endpoint-group h3 {
            background: #f8f9fa;
            margin: 0;
            padding: 15px 20px;
            color: #333;
            border-bottom: 1px solid #e0e0e0;
        }
        .endpoint {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
        }
        .endpoint:last-child {
            border-bottom: none;
        }
        .method {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            color: white;
            min-width: 60px;
            text-align: center;
            margin-right: 10px;
        }
        .method.get { background: #4caf50; }
        .method.post { background: #2196f3; }
        .method.put { background: #ff9800; }
        .method.delete { background: #f44336; }
        .method.patch { background: #9c27b0; }
        .path {
            font-family: "Consolas", "Monaco", monospace;
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            margin-left: 10px;
        }
        .description {
            color: #666;
            margin-top: 8px;
            font-size: 0.9em;
        }
        .example-section {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #1976d2;
        }
        .example-section strong {
            color: #1976d2;
            display: block;
            margin: 10px 0 5px 0;
            font-size: 0.9em;
        }
        .example-section .code {
            margin: 5px 0 15px 0;
            font-size: 0.85em;
        }
        .test-section {
            margin: 15px 0;
        }
        .test-section strong {
            color: #1976d2;
            display: block;
            margin-bottom: 8px;
        }
        .copy-code {
            background: #263238;
            color: #eeffff;
            padding: 12px;
            border-radius: 6px;
            font-family: "Consolas", "Monaco", monospace;
            font-size: 0.85em;
            overflow-x: auto;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid transparent;
            position: relative;
        }
        .copy-code:hover {
            border-color: #1976d2;
            background: #37474f;
        }
        .copy-code:active {
            background: #455a64;
        }
        .copy-code::after {
            content: "📋 Click to copy";
            position: absolute;
            top: 8px;
            right: 12px;
            background: #1976d2;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.7em;
            opacity: 0;
            transition: opacity 0.2s;
        }
        .copy-code:hover::after {
            opacity: 1;
        }
        .copied {
            border-color: #4caf50 !important;
            background: #2e7d32 !important;
        }
        .copied::after {
            content: "✅ Copied!" !important;
            background: #4caf50 !important;
            opacity: 1 !important;
        }
        .btn-group {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            margin: 0 10px;
            padding: 12px 24px;
            background: #1976d2;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn:hover {
            background: #1565c0;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .btn.secondary {
            background: #757575;
        }
        .btn.secondary:hover {
            background: #616161;
        }
        .info-box {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-box h4 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        .warning-box {
            background: #fff3e0;
            border: 1px solid #ffcc02;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .warning-box h4 {
            margin: 0 0 10px 0;
            color: #f57600;
        }
        .code {
            background: #263238;
            color: #eeffff;
            padding: 15px;
            border-radius: 6px;
            font-family: "Consolas", "Monaco", monospace;
            font-size: 0.9em;
            overflow-x: auto;
            margin: 10px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            border-top: 1px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Form Builder API</h1>
            <p>Comprehensive API for building, managing, and processing dynamic forms with payment integration</p>
        </div>
        
        <div class="content">
            <div class="btn-group">
                <a href="' . $baseUrl . '/api/docs/json" target="_blank" class="btn">📄 JSON Specification</a>
                <a href="' . $baseUrl . '/api/docs/yaml" target="_blank" class="btn secondary">📝 YAML Specification</a>
            </div>

            <div class="info-box">
                <h4>📋 API Information</h4>
                <p><strong>Base URL:</strong> <code>' . $baseUrl . '</code></p>
                <p><strong>Version:</strong> 1.0.0</p>
                <p><strong>Authentication:</strong> Bearer Token (JWT)</p>
            </div>

            <div class="warning-box">
                <h4>⚠️ Content Security Policy Notice</h4>
                <p>This simplified documentation is displayed because external resources (Swagger UI) are blocked by Content Security Policy. 
                Use the JSON/YAML links above to access the full OpenAPI specification.</p>
            </div>

            <div class="section">
                <h2>🔍 Health & Status</h2>
                <div class="endpoint-group">
                    <h3>System Health Checks</h3>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/api/health</span>
                        <div class="description">Check if the API server is running and healthy</div>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/api/test-cors</span>
                        <div class="description">Test Cross-Origin Resource Sharing (CORS) configuration</div>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/api/rate-limit-status</span>
                        <div class="description">Check current rate limiting status and configuration</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>🔐 Authentication</h2>
                <div class="endpoint-group">
                    <h3>User Authentication & Management</h3>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/auth/register</span>
                        <div class="description">Register a new user account with email and password</div>
                        <div class="example-section">
                            <strong>📝 Request Body:</strong>
                            <div class="code">{
  "name": "John Doe",
  "email": "john.doe@example.com", 
  "password": "SecurePassword123!",
  "confirmPassword": "SecurePassword123!"
}</div>
                            <strong>✅ Response (201):</strong>
                            <div class="code">{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": 123,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "role": "user",
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
  }
}</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/auth/login</span>
                        <div class="description">Authenticate user with email and password</div>
                        <div class="example-section">
                            <strong>📝 Request Body:</strong>
                            <div class="code">{
  "email": "john.doe@example.com",
  "password": "SecurePassword123!"
}</div>
                            <strong>✅ Response (200):</strong>
                            <div class="code">{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "role": "user"
    },
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "expiresIn": 3600
  }
}</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/api/auth/profile</span>
                        <div class="description">Get current user profile information</div>
                        <div class="example-section">
                            <strong>🔑 Headers:</strong>
                            <div class="code">Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...</div>
                            <strong>✅ Response (200):</strong>
                            <div class="code">{
  "success": true,
  "data": {
    "id": 123,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "role": "user",
    "isActive": true,
    "createdAt": "2024-01-15T10:30:00Z",
    "updatedAt": "2024-01-20T14:22:00Z"
  }
}</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/auth/change-password</span>
                        <div class="description">Change user password</div>
                        <div class="example-section">
                            <strong>📝 Request Body:</strong>
                            <div class="code">{
  "currentPassword": "OldPassword123!",
  "newPassword": "NewSecurePassword456!",
  "confirmPassword": "NewSecurePassword456!"
}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>📋 Forms Management</h2>
                <div class="endpoint-group">
                    <h3>Form CRUD Operations</h3>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/api/forms</span>
                        <div class="description">Retrieve a paginated list of forms</div>
                        <div class="example-section">
                            <strong>🔍 Query Parameters:</strong>
                            <div class="code">?page=1&limit=20&search=contact&status=active</div>
                            <strong>✅ Response (200):</strong>
                            <div class="code">{
  "success": true,
  "data": {
    "forms": [
      {
        "id": 1,
        "title": "Contact Form",
        "description": "Simple contact form for website",
        "status": "active",
        "fields": [
          {
            "id": "name",
            "type": "text",
            "label": "Full Name",
            "required": true
          },
          {
            "id": "email", 
            "type": "email",
            "label": "Email Address",
            "required": true
          }
        ],
        "createdAt": "2024-01-15T10:30:00Z"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 45,
      "pages": 3
    }
  }
}</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/forms</span>
                        <div class="description">Create a new form with specified fields</div>
                        <div class="example-section">
                            <strong>📝 Request Body:</strong>
                            <div class="code">{
  "title": "Event Registration Form",
  "description": "Registration form for annual conference",
  "status": "active",
  "settings": {
    "requireAuth": false,
    "allowEdit": true,
    "emailNotifications": true
  },
  "fields": [
    {
      "id": "fullName",
      "type": "text",
      "label": "Full Name",
      "placeholder": "Enter your full name",
      "required": true,
      "validation": {
        "minLength": 2,
        "maxLength": 100
      }
    },
    {
      "id": "email",
      "type": "email", 
      "label": "Email Address",
      "placeholder": "your.email@example.com",
      "required": true
    },
    {
      "id": "phone",
      "type": "tel",
      "label": "Phone Number",
      "placeholder": "+1 (555) 123-4567",
      "required": false
    },
    {
      "id": "eventType",
      "type": "select",
      "label": "Event Type",
      "required": true,
      "options": [
        {"value": "conference", "label": "Main Conference"},
        {"value": "workshop", "label": "Workshop Only"},
        {"value": "networking", "label": "Networking Event"}
      ]
    },
    {
      "id": "dietaryRestrictions",
      "type": "checkbox",
      "label": "Dietary Restrictions",
      "options": [
        {"value": "vegetarian", "label": "Vegetarian"},
        {"value": "vegan", "label": "Vegan"},
        {"value": "gluten-free", "label": "Gluten Free"},
        {"value": "none", "label": "No Restrictions"}
      ]
    },
    {
      "id": "comments",
      "type": "textarea",
      "label": "Additional Comments",
      "placeholder": "Any special requirements or questions?",
      "required": false,
      "validation": {
        "maxLength": 500
      }
    }
  ],
  "payment": {
    "enabled": true,
    "amount": 99.99,
    "currency": "USD",
    "methods": ["stripe", "bank_transfer"]
  }
}</div>
                            <strong>✅ Response (201):</strong>
                            <div class="code">{
  "success": true,
  "message": "Form created successfully",
  "data": {
    "id": 42,
    "title": "Event Registration Form",
    "customUrl": "event-registration-2024",
    "publicUrl": "' . $baseUrl . '/api/forms/public/event-registration-2024",
    "adminUrl": "' . $baseUrl . '/admin/forms/42",
    "createdAt": "2024-01-20T15:30:00Z"
  }
}</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/api/forms/{id}</span>
                        <div class="description">Get specific form by ID</div>
                        <div class="example-section">
                            <strong>🔍 Example:</strong>
                            <div class="code">GET /api/forms/42</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method put">PUT</span>
                        <span class="path">/api/forms/{id}</span>
                        <div class="description">Update existing form</div>
                        <div class="example-section">
                            <strong>📝 Request Body (partial update):</strong>
                            <div class="code">{
  "title": "Updated Event Registration Form",
  "status": "inactive",
  "settings": {
    "allowEdit": false
  }
}</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/forms/{id}/duplicate</span>
                        <div class="description">Create a copy of existing form</div>
                        <div class="example-section">
                            <strong>📝 Request Body:</strong>
                            <div class="code">{
  "title": "Event Registration Form 2025",
  "copySubmissions": false
}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>📊 Form Submissions</h2>
                <div class="endpoint-group">
                    <h3>Submission Management</h3>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/submissions</span>
                        <div class="description">Submit form data (public endpoint)</div>
                        <div class="example-section">
                            <strong>📝 Request Body:</strong>
                            <div class="code">{
  "formId": 42,
  "data": {
    "fullName": "Jane Smith",
    "email": "jane.smith@example.com",
    "phone": "+1 (555) 987-6543",
    "eventType": "conference",
    "dietaryRestrictions": ["vegetarian", "gluten-free"],
    "comments": "Looking forward to the keynote sessions!"
  },
  "payment": {
    "method": "stripe",
    "amount": 99.99,
    "currency": "USD"
  },
  "metadata": {
    "source": "website",
    "referrer": "google-ads",
    "userAgent": "Mozilla/5.0..."
  }
}</div>
                            <strong>✅ Response (201):</strong>
                            <div class="code">{
  "success": true,
  "message": "Form submitted successfully",
  "data": {
    "submissionId": "sub_abc123def456",
    "uniqueId": "CONF2024-001234",
    "status": "pending_payment",
    "editCode": "EDIT789",
    "editUrl": "' . $baseUrl . '/submissions/sub_abc123def456?code=EDIT789",
    "paymentUrl": "' . $baseUrl . '/payment/sub_abc123def456",
    "submittedAt": "2024-01-20T16:45:00Z"
  }
}</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/api/submissions</span>
                        <div class="description">Get all form submissions (authenticated)</div>
                        <div class="example-section">
                            <strong>🔍 Query Parameters:</strong>
                            <div class="code">?formId=42&status=completed&startDate=2024-01-01&endDate=2024-01-31&page=1&limit=50</div>
                            <strong>✅ Response (200):</strong>
                            <div class="code">{
  "success": true,
  "data": {
    "submissions": [
      {
        "id": "sub_abc123def456",
        "uniqueId": "CONF2024-001234",
        "formId": 42,
        "formTitle": "Event Registration Form",
        "status": "completed",
        "data": {
          "fullName": "Jane Smith",
          "email": "jane.smith@example.com",
          "eventType": "conference"
        },
        "payment": {
          "status": "completed",
          "amount": 99.99,
          "method": "stripe",
          "transactionId": "pi_abc123def456"
        },
        "submittedAt": "2024-01-20T16:45:00Z"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 50,
      "total": 127,
      "pages": 3
    },
    "summary": {
      "totalSubmissions": 127,
      "completedPayments": 89,
      "pendingPayments": 23,
      "totalRevenue": 8811.00
    }
  }
}</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method patch">PATCH</span>
                        <span class="path">/api/submissions/{id}/status</span>
                        <div class="description">Update submission status</div>
                        <div class="example-section">
                            <strong>📝 Request Body:</strong>
                            <div class="code">{
  "status": "approved",
  "adminNotes": "Verified payment and documentation. Approved for event access.",
  "notifyUser": true
}</div>
                            <strong>✅ Response (200):</strong>
                            <div class="code">{
  "success": true,
  "message": "Submission status updated successfully",
  "data": {
    "id": "sub_abc123def456",
    "status": "approved",
    "updatedAt": "2024-01-20T17:30:00Z"
  }
}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>💳 Payment Integration</h2>
                <div class="endpoint-group">
                    <h3>Payment Processing</h3>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/payment/stripe/create-intent</span>
                        <div class="description">Create Stripe payment intent</div>
                        <div class="example-section">
                            <strong>📝 Request Body:</strong>
                            <div class="code">{
  "submissionId": "sub_abc123def456",
  "amount": 9999,
  "currency": "usd",
  "description": "Event Registration Payment - CONF2024-001234",
  "metadata": {
    "submissionId": "sub_abc123def456",
    "formId": "42",
    "userEmail": "jane.smith@example.com"
  }
}</div>
                            <strong>✅ Response (200):</strong>
                            <div class="code">{
  "success": true,
  "data": {
    "clientSecret": "pi_abc123def456_secret_xyz789",
    "paymentIntentId": "pi_abc123def456",
    "amount": 9999,
    "currency": "usd",
    "status": "requires_payment_method"
  }
}</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/payment/bank-transfer</span>
                        <div class="description">Process bank transfer payment</div>
                        <div class="example-section">
                            <strong>📝 Request Body:</strong>
                            <div class="code">{
  "submissionId": "sub_abc123def456",
  "amount": 99.99,
  "currency": "USD",
  "bankDetails": {
    "accountHolder": "Jane Smith",
    "bankName": "Chase Bank",
    "accountNumber": "****1234",
    "routingNumber": "021000021",
    "transactionId": "TXN789456123"
  },
  "receiptFile": "payment_receipt_20240120.pdf",
  "notes": "Transfer completed on 2024-01-20"
}</div>
                            <strong>✅ Response (201):</strong>
                            <div class="code">{
  "success": true,
  "message": "Bank transfer payment recorded. Pending admin approval.",
  "data": {
    "paymentId": "pay_bank_abc123",
    "status": "pending_approval",
    "submissionId": "sub_abc123def456",
    "amount": 99.99,
    "receiptUrl": "' . $baseUrl . '/uploads/receipts/payment_receipt_20240120.pdf"
  }
}</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/bkash/create</span>
                        <div class="description">Create bKash payment</div>
                        <div class="example-section">
                            <strong>📝 Request Body:</strong>
                            <div class="code">{
  "submissionId": "sub_abc123def456",
  "amount": "99.99",
  "currency": "BDT",
  "intent": "sale",
  "merchantInvoiceNumber": "CONF2024-001234"
}</div>
                            <strong>✅ Response (200):</strong>
                            <div class="code">{
  "success": true,
  "data": {
    "paymentID": "TR001234567890123456789",
    "createTime": "2024-01-20T16:45:00Z",
    "orgLogo": "https://www.bkash.com/logo.png",
    "orgName": "Form Builder App",
    "transactionStatus": "Initiated",
    "amount": "99.99",
    "currency": "BDT",
    "bkashURL": "https://checkout.pay.bka.sh/v1.2.0-beta/checkout/payment/TR001234567890123456789"
  }
}</div>
                        </div>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/payment/stripe/webhook</span>
                        <div class="description">Stripe webhook endpoint (handles payment confirmations)</div>
                        <div class="example-section">
                            <strong>⚠️ Note:</strong> This endpoint is called automatically by Stripe. Include <code>Stripe-Signature</code> header for verification.
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>👨‍💼 Administration</h2>
                <div class="endpoint-group">
                    <h3>Admin Operations (Requires Admin Role)</h3>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/api/admin/users</span>
                        <div class="description">Manage system users</div>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/api/admin/stats</span>
                        <div class="description">Get system statistics</div>
                    </div>
                    <div class="endpoint">
                        <span class="method get">GET</span>
                        <span class="path">/api/admin/settings</span>
                        <div class="description">System settings management</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>📤 Data Export</h2>
                <div class="endpoint-group">
                    <h3>Export Operations</h3>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/export/csv</span>
                        <div class="description">Export data as CSV file</div>
                    </div>
                    <div class="endpoint">
                        <span class="method post">POST</span>
                        <span class="path">/api/export/pdf</span>
                        <div class="description">Export data as PDF report</div>
                    </div>
                </div>
            </div>

            <div class="info-box">
                <h4>🔧 Testing the API</h4>
                <p>You can test the API endpoints using curl commands. Click any command to copy it to your clipboard:</p>
                
                <div class="test-section">
                    <strong>🔍 Health Check:</strong>
                    <div class="copy-code" onclick="copyToClipboard(this)">curl -X GET "' . $baseUrl . '/api/health" -H "Content-Type: application/json"</div>
                </div>

                <div class="test-section">
                    <strong>👤 User Registration:</strong>
                    <div class="copy-code" onclick="copyToClipboard(this)">curl -X POST "' . $baseUrl . '/api/auth/register" -H "Content-Type: application/json" -d \'{"name":"Test User","email":"test@example.com","password":"SecurePassword123!","confirmPassword":"SecurePassword123!"}\'</div>
                </div>

                <div class="test-section">
                    <strong>🔐 User Login:</strong>
                    <div class="copy-code" onclick="copyToClipboard(this)">curl -X POST "' . $baseUrl . '/api/auth/login" -H "Content-Type: application/json" -d \'{"email":"test@example.com","password":"SecurePassword123!"}\'</div>
                </div>

                <div class="test-section">
                    <strong>📋 Create Form (requires token):</strong>
                    <div class="copy-code" onclick="copyToClipboard(this)">curl -X POST "' . $baseUrl . '/api/forms" -H "Content-Type: application/json" -H "Authorization: Bearer YOUR_JWT_TOKEN" -d \'{"title":"Test Contact Form","description":"A simple test form","fields":[{"id":"name","type":"text","label":"Name","required":true},{"id":"email","type":"email","label":"Email","required":true}]}\'</div>
                </div>

                <div class="test-section">
                    <strong>📊 Submit Form Data:</strong>
                    <div class="copy-code" onclick="copyToClipboard(this)">curl -X POST "' . $baseUrl . '/api/submissions" -H "Content-Type: application/json" -d \'{"formId":1,"data":{"name":"John Doe","email":"john@example.com"}}\'</div>
                </div>

                <div class="test-section">
                    <strong>🗂️ Get Forms (requires token):</strong>
                    <div class="copy-code" onclick="copyToClipboard(this)">curl -X GET "' . $baseUrl . '/api/forms?page=1&limit=10" -H "Authorization: Bearer YOUR_JWT_TOKEN"</div>
                </div>

                <p style="margin-top: 20px; font-size: 0.9em; color: #666;">
                    💡 <strong>Tip:</strong> Replace <code>YOUR_JWT_TOKEN</code> with the actual token received from the login endpoint.
                </p>
            </div>
        </div>

        <div class="footer">
            <p>📚 For the complete OpenAPI 3.0 specification with detailed schemas and examples, use the JSON/YAML links above.</p>
        </div>
    </div>

    <script>
        function copyToClipboard(element) {
            const text = element.textContent || element.innerText;
            
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    showCopySuccess(element);
                }).catch(function(err) {
                    fallbackCopyTextToClipboard(text, element);
                });
            } else {
                fallbackCopyTextToClipboard(text, element);
            }
        }

        function fallbackCopyTextToClipboard(text, element) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand("copy");
                if (successful) {
                    showCopySuccess(element);
                }
            } catch (err) {
                console.error("Fallback: Could not copy text", err);
            }
            
            document.body.removeChild(textArea);
        }

        function showCopySuccess(element) {
            element.classList.add("copied");
            setTimeout(function() {
                element.classList.remove("copied");
            }, 2000);
        }
    </script>
</body>
</html>';
    }
}
