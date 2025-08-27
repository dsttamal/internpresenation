@echo off
REM API Testing Script for Windows
REM Tests all major endpoints of the Form Builder PHP backend

set BASE_URL=http://localhost:5000
set API_URL=%BASE_URL%/api

echo ==========================================
echo Form Builder API Testing Script
echo ==========================================
echo Base URL: %BASE_URL%
echo API URL: %API_URL%
echo.

echo === Health Checks ===
curl -s "%API_URL%/health"
echo.
curl -s "%API_URL%/test-cors"
echo.
curl -s "%API_URL%/rate-limit-status"
echo.

echo === Documentation ===
echo Testing Swagger UI endpoint...
curl -s -o nul -w "Status: %%{http_code}" "%API_URL%/docs"
echo.
echo Testing OpenAPI JSON spec...
curl -s "%API_URL%/docs/json" | head -20
echo.
echo Testing debug swagger info...
curl -s "%API_URL%/debug-swagger"
echo.

echo === Authentication ===
echo Testing user registration...
curl -s -X POST -H "Content-Type: application/json" -d "{\"name\":\"Test User\",\"email\":\"test@example.com\",\"password\":\"password123\"}" "%API_URL%/auth/register"
echo.
echo Testing user login...
curl -s -X POST -H "Content-Type: application/json" -d "{\"email\":\"test@example.com\",\"password\":\"password123\"}" "%API_URL%/auth/login"
echo.

echo === Forms (Public) ===
echo Testing get form by custom URL...
curl -s "%API_URL%/forms/public/sample-form"
echo.

echo === Forms (Protected - will fail without auth) ===
echo Testing list all forms...
curl -s "%API_URL%/forms"
echo.
echo Testing get my forms...
curl -s "%API_URL%/forms/my"
echo.

echo === Submissions (Public) ===
echo Testing create submission...
curl -s -X POST -H "Content-Type: application/json" -d "{\"formId\":1,\"data\":{\"name\":\"John Doe\",\"email\":\"john@example.com\"}}" "%API_URL%/submissions"
echo.

echo === Payment Methods ===
echo Testing get payment methods...
curl -s "%API_URL%/settings/payment-methods"
echo.

echo === Admin (Public Settings) ===
echo Testing get public settings...
curl -s "%API_URL%/settings"
echo.

echo === Export (Protected - will fail without auth) ===
echo Testing export CSV...
curl -s -X POST -H "Content-Type: application/json" -d "{\"formId\":1,\"startDate\":\"2024-01-01\",\"endDate\":\"2024-12-31\"}" "%API_URL%/export/csv"
echo.

echo.
echo ==========================================
echo Test completed!
echo ==========================================
echo.
echo Note: Protected endpoints will return 401/403 without proper authentication.
echo To test authenticated endpoints, you need to:
echo 1. Register/login to get a JWT token
echo 2. Include the token in Authorization header: "Bearer <token>"
echo.
echo Example authenticated request:
echo curl -H "Authorization: Bearer <your-jwt-token>" %API_URL%/forms

pause
