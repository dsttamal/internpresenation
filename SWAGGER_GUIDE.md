# 📚 Swagger/OpenAPI Documentation Guide

## Overview

The PHP Form Builder Backend includes comprehensive API documentation using Swagger/OpenAPI 3.0 specification. This provides interactive documentation that allows you to explore, test, and understand all API endpoints.

## 🌐 Accessing the Documentation

### Swagger UI (Interactive Documentation)
```
http://localhost:5000/api/docs
```
- Interactive web interface
- Test API endpoints directly
- View request/response examples
- Authentication support

### OpenAPI Specification Files
```
http://localhost:5000/api/docs/json    # JSON format
http://localhost:5000/api/docs/yaml    # YAML format
```

## 🚀 Quick Start

### 1. Start the Server
```bash
cd phpbackend
composer run serve
# or
php -S localhost:5000 -t public
```

### 2. Install Swagger Dependencies
```bash
composer install
# This installs zircote/swagger-php package
```

### 3. Access Documentation
Open your browser and go to: `http://localhost:5000/api/docs`

## 📖 Documentation Features

### ✅ What's Documented

1. **Authentication Endpoints**
   - Registration (`POST /api/auth/register`)
   - Login (`POST /api/auth/login`)
   - Profile (`GET /api/auth/me`)
   - Password change, logout, etc.

2. **Form Management**
   - Get all forms (`GET /api/forms`)
   - Create form (`POST /api/forms`)
   - Update form (`PUT /api/forms/{id}`)
   - Delete form (`DELETE /api/forms/{id}`)
   - Form analytics and more

3. **Form Submissions**
   - Submit to form (`POST /api/forms/{id}/submit`)
   - Get submissions (`GET /api/submissions`)
   - Update submission status
   - File uploads

4. **Payment Processing**
   - Stripe integration
   - bKash payments
   - Payment status updates

5. **Export Functionality**
   - CSV exports
   - PDF generation
   - Data filtering

6. **Admin Functions**
   - User management
   - System settings
   - Permissions

### 📋 Schema Definitions

All data models are documented with schemas:
- **User** - User account information
- **Form** - Form structure and metadata
- **Submission** - Form submission data
- **ApiResponse** - Standard API response format
- **Error responses** - Validation, authorization, server errors

## 🔧 Generating Documentation

### Automatic Generation
Documentation is generated automatically when you access `/api/docs` for the first time.

### Manual Generation
```bash
# Generate OpenAPI spec files
php generate-docs.php

# Or using batch script (Windows)
generate-docs.bat

# Using composer (if available)
composer run docs
```

### Generated Files
- `public/openapi.json` - JSON specification
- `public/openapi.yaml` - YAML specification

## 🧪 Testing APIs via Swagger UI

### 1. Authentication
1. Go to `/api/docs`
2. Find the **Authentication** section
3. Test **POST /api/auth/register** to create a user
4. Test **POST /api/auth/login** to get a JWT token
5. Copy the JWT token from the response

### 2. Authorization
1. Click the **🔒 Authorize** button at the top
2. Enter: `Bearer YOUR_JWT_TOKEN`
3. Click **Authorize**
4. Now you can test protected endpoints

### 3. Testing Endpoints
1. Click on any endpoint to expand it
2. Click **Try it out**
3. Fill in required parameters
4. Click **Execute**
5. View the response

## 📝 Adding Documentation to New Endpoints

### Basic Endpoint Documentation
```php
/**
 * @OA\Get(
 *     path="/api/example",
 *     tags={"Example"},
 *     summary="Example endpoint",
 *     description="Detailed description of what this endpoint does",
 *     @OA\Response(
 *         response=200,
 *         description="Success response",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Success"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     )
 * )
 */
public function example(Request $request): Response
{
    // Implementation
}
```

### Protected Endpoint
```php
/**
 * @OA\Get(
 *     path="/api/protected",
 *     tags={"Protected"},
 *     summary="Protected endpoint",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response=200, description="Success"),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */
```

### Request Body Documentation
```php
/**
 * @OA\Post(
 *     path="/api/create",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name", "email"},
 *             @OA\Property(property="name", type="string", example="John Doe"),
 *             @OA\Property(property="email", type="string", format="email")
 *         )
 *     )
 * )
 */
```

### Parameters Documentation
```php
/**
 * @OA\Get(
 *     path="/api/items/{id}",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer", minimum=1)
 *     ),
 *     @OA\Parameter(
 *         name="include",
 *         in="query",
 *         required=false,
 *         @OA\Schema(type="string")
 *     )
 * )
 */
```

## 🎨 Customizing Swagger UI

### Custom Styling
The Swagger UI includes custom styling in the `SwaggerController.php`:
- Custom colors and theme
- Logo customization
- Layout modifications

### Configuration Options
You can modify the Swagger UI configuration in `SwaggerController::getSwaggerUI()`:

```javascript
SwaggerUIBundle({
    url: "/api/docs/json",
    dom_id: "#swagger-ui",
    deepLinking: true,
    tryItOutEnabled: true,
    // Add more configuration options here
});
```

## 🔍 API Documentation Structure

### Tags (Categories)
- **Authentication** - User auth endpoints
- **Forms** - Form management
- **Submissions** - Form submissions
- **Payments** - Payment processing
- **File Upload** - File handling
- **Export** - Data export
- **Admin** - Administrative functions
- **Health** - System health checks

### Security Schemes
- **bearerAuth** - JWT Bearer token authentication

### Common Response Schemas
- **ApiResponse** - Standard success response
- **ValidationError** - Input validation errors
- **UnauthorizedError** - Authentication errors
- **ForbiddenError** - Authorization errors
- **NotFoundError** - Resource not found
- **ServerError** - Internal server errors

## 🛠️ Troubleshooting

### Documentation Not Loading
1. Check if server is running: `http://localhost:5000/api/health`
2. Verify Swagger dependency is installed: `composer show zircote/swagger-php`
3. Check for PHP errors in logs: `tail -f logs/error.log`

### Missing Endpoints
1. Ensure annotations are properly formatted
2. Check if files are being scanned in `generate-docs.php`
3. Regenerate documentation: `php generate-docs.php`

### Authentication Issues in Swagger UI
1. Register a test user via `/api/auth/register`
2. Login via `/api/auth/login` to get a token
3. Use the 🔒 Authorize button with format: `Bearer YOUR_TOKEN`

### Annotation Errors
1. Check PHP syntax: `php -l src/Controllers/YourController.php`
2. Validate OpenAPI syntax: Use online validators
3. Check for missing `@OA\` namespace

## 📱 Mobile and API Client Integration

### Export OpenAPI Spec
```bash
# Download JSON spec
curl http://localhost:5000/api/docs/json > api-spec.json

# Download YAML spec  
curl http://localhost:5000/api/docs/yaml > api-spec.yaml
```

### Use with API Clients
- **Postman**: Import OpenAPI spec to generate collection
- **Insomnia**: Import spec for request templates
- **Code Generation**: Use tools like OpenAPI Generator for client SDKs

## 📈 Benefits of API Documentation

1. **Developer Experience**: Easy to understand and test APIs
2. **Team Collaboration**: Shared understanding of API contracts
3. **Client Development**: Frontend developers can work independently
4. **API Testing**: Built-in testing capabilities
5. **Documentation Maintenance**: Docs stay in sync with code
6. **Standards Compliance**: Follows OpenAPI 3.0 specification

## 🎯 Next Steps

1. **Explore the Documentation**: Visit `http://localhost:5000/api/docs`
2. **Test Authentication**: Register and login to get JWT tokens
3. **Try Protected Endpoints**: Use the authorization feature
4. **Add More Documentation**: Document your custom endpoints
5. **Integrate with Frontend**: Use the API specification for client development

---

**Happy documenting! 📚✨**
