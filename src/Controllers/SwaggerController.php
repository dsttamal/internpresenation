<?php

namespace App\Controllers;

use App\Core\Response;

/**
 * @OA\Tag(
 *     name="Documentation",
 *     description="API Documentation endpoints"
 * )
 */
class SwaggerController
{
    /**
     * @OA\Get(
     *     path="/api/docs",
     *     tags={"Documentation"},
     *     summary="Get Swagger UI",
     *     description="Displays the interactive Swagger UI for API documentation",
     *     @OA\Response(
     *         response=200,
     *         description="Swagger UI HTML page",
     *         @OA\MediaType(
     *             mediaType="text/html",
     *             @OA\Schema(type="string")
     *         )
     *     )
     * )
     */
    public function ui()
    {
        $html = $this->getSwaggerUI();
        return Response::html($html);
    }

    /**
     * @OA\Get(
     *     path="/api/docs/json",
     *     tags={"Documentation"},
     *     summary="Get OpenAPI JSON specification",
     *     description="Returns the complete OpenAPI specification in JSON format",
     *     @OA\Response(
     *         response=200,
     *         description="OpenAPI specification",
     *         @OA\JsonContent(type="object")
     *     )
     * )
     */
    public function json()
    {
        $openapi = $this->generateOpenApiSpec();
        return Response::json($openapi);
    }

    /**
     * @OA\Get(
     *     path="/api/docs/yaml",
     *     tags={"Documentation"},
     *     summary="Get OpenAPI YAML specification",
     *     description="Returns the complete OpenAPI specification in YAML format",
     *     @OA\Response(
     *         response=200,
     *         description="OpenAPI specification in YAML format",
     *         @OA\MediaType(
     *             mediaType="application/x-yaml",
     *             @OA\Schema(type="string")
     *         )
     *     )
     * )
     */
    public function yaml()
    {
        $openapi = $this->generateOpenApiSpec();
        $yaml = \Symfony\Component\Yaml\Yaml::dump($openapi, 4, 2);
        
        return Response::make($yaml, 200, [
            'Content-Type' => 'application/x-yaml'
        ]);
    }

    private function generateOpenApiSpec()
    {
        try {
            $openapi = \OpenApi\Generator::scan([
                __DIR__ . '/../Controllers',
                __DIR__ . '/../Models',
                __DIR__ . '/../Services',
                __DIR__ . '/../OpenApi',
                __DIR__ . '/../../routes'
            ]);

            return json_decode($openapi->toJson(), true);
        } catch (\Exception $e) {
            // Return a basic OpenAPI spec if generation fails
            return [
                'openapi' => '3.0.0',
                'info' => [
                    'title' => 'Form Builder API',
                    'version' => '1.0.0',
                    'description' => 'API documentation temporarily unavailable. Error: ' . $e->getMessage()
                ],
                'servers' => [
                    ['url' => 'http://localhost:5000', 'description' => 'Development server']
                ],
                'paths' => [
                    '/api/health' => [
                        'get' => [
                            'summary' => 'Health check',
                            'responses' => [
                                '200' => ['description' => 'Server is healthy']
                            ]
                        ]
                    ]
                ]
            ];
        }
    }

    private function getSwaggerUI()
    {
        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Form Builder API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui.css" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.10.5/favicon-32x32.png" sizes="32x32" />
    <link rel="icon" type="image/png" href="https://unpkg.com/swagger-ui-dist@5.10.5/favicon-16x16.png" sizes="16x16" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
        }
        .topbar {
            background: #1976d2 !important;
        }
        .topbar .download-url-wrapper {
            display: none;
        }
        .swagger-ui .topbar .topbar-wrapper .link img {
            content: url("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiByeD0iOCIgZmlsbD0iIzFFODhFNSIvPgo8cGF0aCBkPSJNMTIgMTJIMjhWMTZIMTJWMTJaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTIgMjBIMjRWMjRIMTJWMjBaIiBmaWxsPSJ3aGl0ZSIvPgo8cGF0aCBkPSJNMTIgMjhIMjhWMzJIMTJWMjhaIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K");
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: "/api/docs/json",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                tryItOutEnabled: true,
                requestInterceptor: function(request) {
                    // Add any default headers here
                    return request;
                },
                responseInterceptor: function(response) {
                    return response;
                }
            });
        };
    </script>
</body>
</html>';
    }
}
