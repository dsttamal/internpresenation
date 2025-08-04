@echo off
echo ================================
echo  BSMMU Alumni - Admin Creator
echo ================================
echo.

REM Check if PHP is available
php --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP is not installed or not in PATH
    echo Please install PHP or add it to your system PATH
    pause
    exit /b 1
)

echo Choose an option:
echo.
echo 1. Interactive Admin Creator (Full featured)
echo 2. Quick Admin Creator (Use predefined credentials)
echo.
set /p choice="Enter your choice (1 or 2): "

if "%choice%"=="1" (
    echo.
    echo Starting Interactive Admin Creator...
    echo.
    php create-admin.php
) else if "%choice%"=="2" (
    echo.
    echo Starting Quick Admin Creator...
    echo Note: Edit quick-admin.php to change the default credentials
    echo.
    php quick-admin.php
) else (
    echo.
    echo Invalid choice. Please run the script again.
)

echo.
pause
