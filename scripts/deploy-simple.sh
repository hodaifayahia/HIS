#!/bin/bash
# deploy-simple.sh

set -e

echo "🚀 Starting Sail deployment..."

# Pull latest changes
git pull origin main

# Stop current containers
./vendor/bin/sail down

# Start containers using existing images
./vendor/bin/sail up -d

# Wait for services
echo "⏳ Waiting for services to start..."
sleep 30

# Install/update dependencies
./vendor/bin/sail composer install --optimize-autoloader
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# Laravel optimizations
./vendor/bin/sail artisan key:generate --force
./vendor/bin/sail artisan migrate --force
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache
./vendor/bin/sail artisan storage:link

echo "✅ Sail deployment complete!"
echo "🌐 Application: http://localhost"
echo "🔧 Or: http://10.47.0.26:8080"
