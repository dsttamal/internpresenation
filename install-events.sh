#!/bin/bash

# 🔧 Quick Dependency Fix for Missing Illuminate Events

echo "🔧 Installing missing Illuminate dependencies..."
echo "=============================================="

# Check if composer.json exists
if [ ! -f "composer.json" ]; then
    echo "❌ composer.json not found! Please run from the phpbackend directory."
    exit 1
fi

# Remove vendor and composer.lock to force fresh install
echo "🗑️  Cleaning old dependencies..."
rm -rf vendor/
rm -f composer.lock

# Clear composer cache
echo "🧹 Clearing composer cache..."
composer clear-cache

echo "📦 Installing dependencies with Illuminate Events support..."
composer require illuminate/events:^10.0 illuminate/container:^10.0 --no-interaction

if [ $? -eq 0 ]; then
    echo "✅ Dependencies installed successfully!"
    echo ""
    echo "🎉 You can now access Swagger documentation:"
    echo "   http://localhost:5000/api/docs"
    echo ""
    echo "🚀 Start the server:"
    echo "   ./start.sh"
else
    echo "❌ Installation failed."
    echo ""
    echo "💡 Manual steps:"
    echo "   1. composer install"
    echo "   2. If that fails, try: composer install --ignore-platform-reqs"
fi
