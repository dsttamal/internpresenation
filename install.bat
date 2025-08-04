@echo off
echo 🚀 Installing Form Builder PHP Backend...

REM Check if PHP is installed
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP is not installed. Please install PHP 8.1 or higher.
    exit /b 1
)

echo ✅ PHP detected

REM Check if Composer is installed
composer --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Composer is not installed. Please install Composer first.
    echo    Visit: https://getcomposer.org/download/
    exit /b 1
)

echo ✅ Composer detected

REM Install dependencies
echo 📦 Installing PHP dependencies...
composer install --no-dev --optimize-autoloader

if %errorlevel% neq 0 (
    echo ❌ Failed to install dependencies
    exit /b 1
)

echo ✅ Dependencies installed

REM Create .env file if it doesn't exist
if not exist .env (
    echo 📝 Creating environment file...
    copy .env.example .env
    echo ✅ Environment file created
    echo ⚠️  Please edit .env file with your configuration
) else (
    echo ✅ Environment file already exists
)

REM Create storage directories
echo 📁 Creating storage directories...
if not exist storage\rate_limits mkdir storage\rate_limits
if not exist uploads mkdir uploads
if not exist logs mkdir logs
if not exist temp mkdir temp

echo ✅ Directories created

REM Test PHP syntax
echo 🔍 Testing PHP syntax...
php -l public/index.php

if %errorlevel% neq 0 (
    echo ❌ PHP syntax errors found
    exit /b 1
)

echo ✅ PHP syntax check passed

echo.
echo 🎉 Installation completed successfully!
echo.
echo Next steps:
echo 1. Edit .env file with your database and other configurations
echo 2. Create MySQL database and import database/schema.sql
echo 3. Start the development server: composer start
echo 4. Or configure your web server to point to the public/ directory
echo.
echo Development server: composer start (will run on http://localhost:5000)
echo API Documentation: Check README.md for endpoint details

pause
