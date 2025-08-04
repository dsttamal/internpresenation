@echo off
REM 🔧 Quick Dependency Fix for Missing Illuminate Events (Windows)

echo 🔧 Installing missing Illuminate dependencies...
echo ==============================================

REM Check if composer.json exists
if not exist "composer.json" (
    echo ❌ composer.json not found! Please run from the phpbackend directory.
    pause
    exit /b 1
)

REM Remove vendor and composer.lock to force fresh install
echo 🗑️  Cleaning old dependencies...
if exist "vendor" rmdir /s /q vendor
if exist "composer.lock" del composer.lock

REM Clear composer cache
echo 🧹 Clearing composer cache...
composer clear-cache

echo 📦 Installing dependencies with Illuminate Events support...
composer require illuminate/events:^10.0 illuminate/container:^10.0 --no-interaction

if %errorlevel% EQU 0 (
    echo ✅ Dependencies installed successfully!
    echo.
    echo 🎉 You can now access Swagger documentation:
    echo    http://localhost:5000/api/docs
    echo.
    echo 🚀 Start the server:
    echo    start.bat
) else (
    echo ❌ Installation failed.
    echo.
    echo 💡 Manual steps:
    echo    1. composer install
    echo    2. If that fails, try: composer install --ignore-platform-reqs
)

pause
