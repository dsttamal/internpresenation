@echo off
REM Windows batch script to generate OpenAPI documentation

echo 🚀 Generating OpenAPI Documentation...
php generate-docs.php
echo.
echo ✅ Documentation generation complete!
pause
