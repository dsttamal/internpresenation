@echo off
REM 🛠️ Fix Composer Dependencies for PHP 8.1 (Windows)

echo 🔧 Fixing Composer Dependencies for PHP 8.1...
echo ==============================================

REM Check current PHP version
for /f "tokens=*" %%i in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%i
echo 🐘 Current PHP Version: %PHP_VERSION%

REM Remove existing composer.lock and vendor directory
if exist "composer.lock" (
    echo 🗑️  Removing composer.lock...
    del composer.lock
)

if exist "vendor" (
    echo 🗑️  Removing vendor directory...
    rmdir /s /q vendor
)

REM Clear composer cache
echo 🧹 Clearing composer cache...
composer clear-cache

REM Install dependencies
echo 📦 Installing compatible dependencies...
composer install

if %errorlevel% EQU 0 (
    echo ✅ Dependencies installed successfully!
    echo.
    echo 🎉 You can now start the server:
    echo    start.bat
    echo    # or
    echo    php -S localhost:5000 -t public
) else (
    echo ❌ Installation failed. Please check the errors above.
    echo.
    echo 💡 Manual steps to try:
    echo    1. composer clear-cache
    echo    2. del composer.lock
    echo    3. composer install --no-dev
    echo    4. Check if all required PHP extensions are installed
)

pause
