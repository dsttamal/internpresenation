#!/usr/bin/env php
<?php

/**
 * Generate OpenAPI Documentation
 * 
 * This script generates the OpenAPI specification from annotations
 * in the source code and saves it to the public directory.
 */

require_once __DIR__ . '/vendor/autoload.php';

use OpenApi\Generator;

echo "🚀 Generating OpenAPI Documentation...\n";

try {
    // Define scan paths
    $scanPaths = [
        __DIR__ . '/src/Controllers',
        __DIR__ . '/src/Models', 
        __DIR__ . '/src/Services',
        __DIR__ . '/src/OpenApi',
        __DIR__ . '/routes'
    ];

    echo "📁 Scanning paths:\n";
    foreach ($scanPaths as $path) {
        echo "   - $path\n";
    }

    // Generate OpenAPI specification
    $openapi = Generator::scan($scanPaths);

    // Save JSON format
    $jsonPath = __DIR__ . '/public/openapi.json';
    file_put_contents($jsonPath, $openapi->toJson());
    echo "✅ JSON specification saved to: $jsonPath\n";

    // Save YAML format
    $yamlPath = __DIR__ . '/public/openapi.yaml';
    file_put_contents($yamlPath, $openapi->toYaml());
    echo "✅ YAML specification saved to: $yamlPath\n";

    // Display summary
    $spec = json_decode($openapi->toJson(), true);
    $pathCount = count($spec['paths'] ?? []);
    $schemaCount = count($spec['components']['schemas'] ?? []);

    echo "\n📊 Documentation Summary:\n";
    echo "   - API Paths: $pathCount\n";
    echo "   - Schemas: $schemaCount\n";
    echo "   - OpenAPI Version: " . ($spec['openapi'] ?? 'Unknown') . "\n";

    echo "\n🌐 Access documentation at:\n";
    echo "   - Swagger UI: http://localhost:5000/api/docs\n";
    echo "   - JSON Spec: http://localhost:5000/openapi.json\n";
    echo "   - YAML Spec: http://localhost:5000/openapi.yaml\n";

    echo "\n🎉 Documentation generated successfully!\n";

} catch (Exception $e) {
    echo "❌ Error generating documentation: " . $e->getMessage() . "\n";
    echo "📝 Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
