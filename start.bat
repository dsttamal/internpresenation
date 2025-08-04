@echo off
setlocal enabledelayedexpansion

REM 🚀 Quick Start Script for PHP Backend (Windows)

echo.
echo 🏃‍♂️ Starting PHP Form Builder Backend...
echo ==================================

REM Check if we're in the right directory
if not exist "composer.json" (
    echo ❌ Error: Please run this script from the phpbackend directory
    echo    cd phpbackend && start.bat
    pause
    exit /b 1
)

REM Check if .env exists
if not exist ".env" (
    echo ⚠️  No .env file found. Creating from template...
    if exist ".env.example" (
        copy ".env.example" ".env" >nul
        echo ✅ Created .env file from template
        echo ⚠️  Please edit .env file with your database credentials before continuing
        echo    notepad .env
        pause
    ) else (
        echo ❌ No .env.example found. Please create .env file manually
        pause
        exit /b 1
    )
)

REM Check if vendor directory exists (dependencies installed)
if not exist "vendor" (
    echo 📦 Installing dependencies...
    composer install
    if errorlevel 1 (
        echo ❌ Failed to install dependencies
        echo 💡 Common solutions:
        echo    1. Clear composer cache: composer clear-cache
        echo    2. Remove composer.lock: del composer.lock
        echo    3. Try install again: composer install
        echo    4. Check PHP version compatibility
        pause
        exit /b 1
    )
    echo ✅ Dependencies installed
)

REM Check PHP version
for /f "tokens=*" %%i in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%i
echo 🐘 PHP Version: !PHP_VERSION!

REM Check PHP version compatibility
for /f "tokens=*" %%i in ('php -r "echo PHP_MAJOR_VERSION;"') do set PHP_MAJOR=%%i
for /f "tokens=*" %%i in ('php -r "echo PHP_MINOR_VERSION;"') do set PHP_MINOR=%%i

if !PHP_MAJOR! LSS 8 (
    echo ❌ PHP 8.1 or higher is required. Current version: !PHP_VERSION!
    echo    Please upgrade PHP and try again
    pause
    exit /b 1
)

if !PHP_MAJOR! EQU 8 if !PHP_MINOR! LSS 1 (
    echo ❌ PHP 8.1 or higher is required. Current version: !PHP_VERSION!
    echo    Please upgrade PHP and try again
    pause
    exit /b 1
)

if !PHP_MAJOR! EQU 8 if !PHP_MINOR! EQU 1 (
    echo ✅ PHP 8.1 detected - using compatible package versions
) else (
    echo ✅ PHP 8.2+ detected - all packages supported
)

REM Check required PHP extensions
echo 🔍 Checking PHP extensions...
set MISSING_EXTENSIONS=

php -m | findstr /C:"pdo" >nul || set MISSING_EXTENSIONS=!MISSING_EXTENSIONS! pdo
php -m | findstr /C:"openssl" >nul || set MISSING_EXTENSIONS=!MISSING_EXTENSIONS! openssl
php -m | findstr /C:"mbstring" >nul || set MISSING_EXTENSIONS=!MISSING_EXTENSIONS! mbstring
php -m | findstr /C:"json" >nul || set MISSING_EXTENSIONS=!MISSING_EXTENSIONS! json
php -m | findstr /C:"curl" >nul || set MISSING_EXTENSIONS=!MISSING_EXTENSIONS! curl

if not "!MISSING_EXTENSIONS!"=="" (
    echo ❌ Missing PHP extensions:!MISSING_EXTENSIONS!
    echo    Please install them and try again
    pause
    exit /b 1
)
echo ✅ All required PHP extensions are installed

REM Test database connection
echo 🗄️  Testing database connection...
php -r "require 'vendor/autoload.php'; $dotenv = Dotenv\Dotenv::createImmutable(__DIR__); $dotenv->load(); try { $pdo = new PDO('mysql:host=' . $_ENV['DB_HOST'] . ';port=' . $_ENV['DB_PORT'] . ';dbname=' . $_ENV['DB_DATABASE'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); echo '✅ Database connection successful\n'; exit(0); } catch (Exception $e) { echo '❌ Database connection failed: ' . $e->getMessage() . '\n'; echo '   Please check your .env database settings\n'; exit(1); }"

if errorlevel 1 (
    echo 💡 To fix database issues:
    echo    1. Ensure MySQL/MariaDB is running
    echo    2. Create database: CREATE DATABASE form_builder;
    echo    3. Update .env with correct credentials
    echo    4. Import schema: mysql -u root -p form_builder ^< database/schema.sql
    pause
)

REM Create directories if they don't exist
echo 📁 Setting up directories...
if not exist "storage" mkdir storage
if not exist "uploads" mkdir uploads
if not exist "logs" mkdir logs
echo ✅ Directories ready

REM Find available port
set PORT=5000
:checkport
netstat -an | findstr ":!PORT! " >nul
if not errorlevel 1 (
    set /a PORT+=1
    goto checkport
)

echo.
echo 🎉 Starting PHP Development Server...
echo ==================================
echo 🌐 Server URL: http://localhost:!PORT!
echo 🔗 API Base: http://localhost:!PORT!/api
echo 🏥 Health Check: http://localhost:!PORT!/api/health
echo 📚 API Docs: http://localhost:!PORT!/api/docs
echo.
echo 📝 Quick Test Commands:
echo    curl http://localhost:!PORT!/api/health
echo    curl http://localhost:!PORT!/api/auth/register -X POST -H "Content-Type: application/json" -d "{\"name\":\"Test\",\"email\":\"test@test.com\",\"password\":\"password123\"}"
echo.
echo 🛑 Press Ctrl+C to stop the server
echo ==================================
echo.

REM Start the server
php -S localhost:!PORT! -t public
