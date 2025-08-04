#!/bin/bash

# 🛠️ Fix Composer Dependencies for PHP 8.1

echo "🔧 Fixing Composer Dependencies for PHP 8.1..."
echo "=============================================="

# Check current PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "🐘 Current PHP Version: $PHP_VERSION"

# Remove existing composer.lock and vendor directory
if [ -f "composer.lock" ]; then
    echo "🗑️  Removing composer.lock..."
    rm composer.lock
fi

if [ -d "vendor" ]; then
    echo "🗑️  Removing vendor directory..."
    rm -rf vendor
fi

# Clear composer cache
echo "🧹 Clearing composer cache..."
composer clear-cache

# Update composer.json for PHP 8.1 compatibility
echo "📝 Updating composer.json for PHP 8.1 compatibility..."

# The composer.json has already been updated with compatible versions

# Install dependencies
echo "📦 Installing compatible dependencies..."
composer install

if [ $? -eq 0 ]; then
    echo "✅ Dependencies installed successfully!"
    echo ""
    echo "🎉 You can now start the server:"
    echo "   ./start.sh"
    echo "   # or"
    echo "   php -S localhost:5000 -t public"
else
    echo "❌ Installation failed. Please check the errors above."
    echo ""
    echo "💡 Manual steps to try:"
    echo "   1. composer clear-cache"
    echo "   2. rm composer.lock"
    echo "   3. composer install --no-dev"
    echo "   4. Check if all required PHP extensions are installed"
fi
