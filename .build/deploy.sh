#!/usr/bin/env bash

set -e

echo "🚀 Starting deployment..."

# Install dependencies with optimized autoloader
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Clear and cache configuration
echo "⚙️  Optimizing configuration..."
php artisan config:cache

# Cache routes
echo "🛣️  Caching routes..."
php artisan route:cache

# Cache views
echo "👁️  Caching views..."
php artisan view:cache

# Cache events (if using event discovery)
echo "📡 Caching events..."
php artisan event:cache

echo "✅ Deployment complete!"

