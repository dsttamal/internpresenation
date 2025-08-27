#!/bin/bash

# API Testing Script
# Tests all major endpoints of the Form Builder PHP backend

BASE_URL="http://localhost:5000"
API_URL="$BASE_URL/api"

echo "=========================================="
echo "Form Builder API Testing Script"
echo "=========================================="
echo "Base URL: $BASE_URL"
echo "API URL: $API_URL"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to test endpoint
test_endpoint() {
    local method=$1
    local endpoint=$2
    local description=$3
    local data=$4
    
    echo -e "${BLUE}Testing:${NC} $method $endpoint - $description"
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "HTTPSTATUS:%{http_code}" -X GET "$API_URL$endpoint")
    elif [ "$method" = "POST" ] && [ -n "$data" ]; then
        response=$(curl -s -w "HTTPSTATUS:%{http_code}" -X POST \
                  -H "Content-Type: application/json" \
                  -d "$data" \
                  "$API_URL$endpoint")
    else
        response=$(curl -s -w "HTTPSTATUS:%{http_code}" -X $method "$API_URL$endpoint")
    fi
    
    # Extract status code
    status_code=$(echo $response | grep -o "HTTPSTATUS:[0-9]*" | cut -d: -f2)
    body=$(echo $response | sed 's/HTTPSTATUS:[0-9]*$//')
    
    if [ "$status_code" -ge 200 ] && [ "$status_code" -lt 300 ]; then
        echo -e "${GREEN}✓ Success${NC} (Status: $status_code)"
    elif [ "$status_code" -ge 400 ] && [ "$status_code" -lt 500 ]; then
        echo -e "${YELLOW}⚠ Client Error${NC} (Status: $status_code)"
    else
        echo -e "${RED}✗ Error${NC} (Status: $status_code)"
    fi
    
    # Pretty print JSON response if it's JSON
    if echo "$body" | jq . >/dev/null 2>&1; then
        echo "$body" | jq . | head -10
    else
        echo "$body" | head -5
    fi
    echo ""
}

echo "=== Health Checks ==="
test_endpoint "GET" "/health" "Health check"
test_endpoint "GET" "/test-cors" "CORS test"
test_endpoint "GET" "/rate-limit-status" "Rate limit status"

echo "=== Documentation ==="
test_endpoint "GET" "/docs" "Swagger UI"
test_endpoint "GET" "/docs/json" "OpenAPI JSON spec"
test_endpoint "GET" "/docs/yaml" "OpenAPI YAML spec"
test_endpoint "GET" "/debug-swagger" "Debug swagger info"

echo "=== Authentication ==="
test_endpoint "POST" "/auth/register" "User registration" '{"name":"Test User","email":"test@example.com","password":"password123"}'
test_endpoint "POST" "/auth/login" "User login" '{"email":"test@example.com","password":"password123"}'

echo "=== Forms (Public) ==="
test_endpoint "GET" "/forms/public/sample-form" "Get form by custom URL"

echo "=== Forms (Protected - will fail without auth) ==="
test_endpoint "GET" "/forms" "List all forms"
test_endpoint "GET" "/forms/my" "Get my forms"
test_endpoint "GET" "/forms/1" "Get specific form"

echo "=== Submissions (Public) ==="
test_endpoint "POST" "/submissions" "Create submission" '{"formId":1,"data":{"name":"John Doe","email":"john@example.com"}}'

echo "=== Payment Methods ==="
test_endpoint "GET" "/settings/payment-methods" "Get payment methods"

echo "=== Admin (Public Settings) ==="
test_endpoint "GET" "/settings" "Get public settings"

echo "=== Export (Protected - will fail without auth) ==="
test_endpoint "POST" "/export/csv" "Export CSV" '{"formId":1,"startDate":"2024-01-01","endDate":"2024-12-31"}'

echo ""
echo "=========================================="
echo "Test completed!"
echo "=========================================="
echo ""
echo "Note: Protected endpoints will return 401/403 without proper authentication."
echo "To test authenticated endpoints, you need to:"
echo "1. Register/login to get a JWT token"
echo "2. Include the token in Authorization header: 'Bearer <token>'"
echo ""
echo "Example authenticated request:"
echo "curl -H \"Authorization: Bearer <your-jwt-token>\" $API_URL/forms"
