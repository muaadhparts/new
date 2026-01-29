#!/bin/bash

# Script to install code quality tools for the project
# Run this script in your local development environment

set -e

echo "🚀 Installing Code Quality Tools..."

# Install Laravel Pint (Code Formatter)
echo "📦 Installing Laravel Pint..."
composer require laravel/pint --dev

# Install PHPStan (Static Analysis)
echo "📦 Installing PHPStan with Larastan..."
composer require phpstan/phpstan --dev
composer require larastan/larastan --dev

# Install PHPUnit (if not already installed)
echo "📦 Checking PHPUnit..."
if ! composer show phpunit/phpunit &> /dev/null; then
    echo "Installing PHPUnit..."
    composer require phpunit/phpunit --dev
fi

echo "✅ All tools installed successfully!"
echo ""
echo "📝 Available commands:"
echo "  - composer lint          # Format code with Pint"
echo "  - composer analyse       # Run PHPStan analysis"
echo "  - php artisan test       # Run tests"
echo ""
echo "🎉 Setup complete!"
