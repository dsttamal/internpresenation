#!/bin/bash

# 🚀 Quick Start Script for PHP Backend

echo "🏃‍♂️ Starting PHP Form Builder Backend..."
echo "=================================="

# Check if we're in the right directory
if [ ! -f "composer.json" ]; then
    echo "❌ Error: Please run this script from the phpbackend directory"
    echo "   cd phpbackend && ./start.sh"
    exit 1
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "⚠️  No .env file found. Creating from template..."
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo "✅ Created .env file from template"
        echo "⚠️  Please edit .env file with your database credentials before continuing"
        echo "   nano .env"
        read -p "Press Enter after updating .env file..."
    else
        echo "❌ No .env.example found. Please create .env file manually"
        exit 1
    fi
fi

# Check if vendor directory exists (dependencies installed)
if [ ! -d "vendor" ]; then
    echo "📦 Installing dependencies..."
    composer install
    if [ $? -ne 0 ]; then
        echo "❌ Failed to install dependencies"
        echo "💡 Common solutions:"
        echo "   1. Clear composer cache: composer clear-cache"
        echo "   2. Remove composer.lock: rm composer.lock"
        echo "   3. Try install again: composer install"
        echo "   4. Check PHP version compatibility"
        exit 1
    fi
    echo "✅ Dependencies installed"
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "🐘 PHP Version: $PHP_VERSION"

# Check if PHP version is compatible
PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")
PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;")

if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 1 ]); then
    echo "❌ PHP 8.1 or higher is required. Current version: $PHP_VERSION"
    echo "   Please upgrade PHP and try again"
    exit 1
fi

if [ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -eq 1 ]; then
    echo "✅ PHP 8.1 detected - using compatible package versions"
elif [ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -ge 2 ]; then
    echo "✅ PHP 8.2+ detected - all packages supported"
fi

# Check required PHP extensions
echo "🔍 Checking PHP extensions..."
REQUIRED_EXTENSIONS=("pdo" "openssl" "mbstring" "json" "curl")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if ! php -m | grep -q "^$ext$"; then
        MISSING_EXTENSIONS+=("$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -ne 0 ]; then
    echo "❌ Missing PHP extensions: ${MISSING_EXTENSIONS[*]}"
    echo "   Please install them and try again"
    exit 1
fi
echo "✅ All required PHP extensions are installed"

# Test database connection
echo "🗄️  Testing database connection..."
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();

try {
    \$pdo = new PDO(
        'mysql:host=' . \$_ENV['DB_HOST'] . ';port=' . \$_ENV['DB_PORT'] . ';dbname=' . \$_ENV['DB_DATABASE'],
        \$_ENV['DB_USERNAME'],
        \$_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo '✅ Database connection successful\n';
    exit(0);
} catch (Exception \$e) {
    echo '❌ Database connection failed: ' . \$e->getMessage() . '\n';
    echo '   Please check your .env database settings\n';
    exit(1);
}
"

if [ $? -ne 0 ]; then
    echo "💡 To fix database issues:"
    echo "   1. Ensure MySQL/MariaDB is running"
    echo "   2. Create database: CREATE DATABASE form_builder;"
    echo "   3. Update .env with correct credentials"
    echo "   4. Import schema: mysql -u root -p form_builder < database/schema.sql"
    read -p "Press Enter to continue anyway or Ctrl+C to exit..."
fi

# Create directories if they don't exist
echo "📁 Setting up directories..."
mkdir -p storage uploads logs
chmod 755 storage uploads logs 2>/dev/null || true
echo "✅ Directories ready"

# Find available port
PORT=5000
while netstat -ln 2>/dev/null | grep -q ":$PORT "; do
    ((PORT++))
done

echo ""
echo "🎉 Starting PHP Development Server..."
echo "=================================="
echo "🌐 Server URL: http://localhost:$PORT"
echo "🔗 API Base: http://localhost:$PORT/api"
echo "🏥 Health Check: http://localhost:$PORT/api/health"
echo "📚 API Docs: http://localhost:$PORT/api/docs"
echo ""
echo "📝 Quick Test Commands:"
echo "   curl http://localhost:$PORT/api/health"
echo "   curl http://localhost:$PORT/api/auth/register -X POST -H 'Content-Type: application/json' -d '{\"name\":\"Test\",\"email\":\"test@test.com\",\"password\":\"password123\"}'"
echo ""
echo "🛑 Press Ctrl+C to stop the server"
echo "=================================="
echo ""

# Start the server
php -S localhost:$PORT -t public
