#!/usr/bin/env bash

set -e

echo "🚀 Starting deployment..."

# Install PHP dependencies with optimized autoloader
echo "📦 Installing PHP dependencies..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Install and build frontend assets
echo "🎨 Building frontend assets..."
npm ci --audit false
npm run build

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

