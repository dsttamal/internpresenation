#!/bin/bash

# Form Builder PHP Backend Installation Script

echo "🚀 Installing Form Builder PHP Backend..."

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP 8.1 or higher."
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
if ! php -r "exit(version_compare(PHP_VERSION, '8.1', '>=') ? 0 : 1);"; then
    echo "❌ PHP 8.1 or higher is required. Current version: $PHP_VERSION"
    exit 1
fi

echo "✅ PHP $PHP_VERSION detected"

# Check if Composer is installed
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    echo "   Visit: https://getcomposer.org/download/"
    exit 1
fi

echo "✅ Composer detected"

# Install dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

if [ $? -ne 0 ]; then
    echo "❌ Failed to install dependencies"
    exit 1
fi

echo "✅ Dependencies installed"

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating environment file..."
    cp .env.example .env
    echo "✅ Environment file created"
    echo "⚠️  Please edit .env file with your configuration"
else
    echo "✅ Environment file already exists"
fi

# Create storage directories
echo "📁 Creating storage directories..."
mkdir -p storage/rate_limits
mkdir -p uploads
mkdir -p logs
mkdir -p temp

# Set permissions
echo "🔒 Setting permissions..."
chmod 755 storage
chmod 755 uploads
chmod 755 logs
chmod 755 temp
chmod -R 755 storage/rate_limits

echo "✅ Permissions set"

# Test PHP syntax
echo "🔍 Testing PHP syntax..."
php -l public/index.php

if [ $? -ne 0 ]; then
    echo "❌ PHP syntax errors found"
    exit 1
fi

echo "✅ PHP syntax check passed"

echo ""
echo "🎉 Installation completed successfully!"
echo ""
echo "Next steps:"
echo "1. Edit .env file with your database and other configurations"
echo "2. Create MySQL database and run: mysql -u username -p database_name < database/schema.sql"
echo "3. Start the development server: composer start"
echo "4. Or configure your web server to point to the public/ directory"
echo ""
echo "Development server: composer start (will run on http://localhost:5000)"
echo "API Documentation: Check README.md for endpoint details"
