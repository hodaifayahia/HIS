#!/bin/bash
# deploy.sh

set -e

echo "🚀 Starting deployment..."

# Pull latest changes
git pull origin main

# Build and start containers
docker compose -f docker-compose.prod.yml down
docker compose -f docker-compose.prod.yml up -d --build

# Wait for services to be ready
echo "⏳ Waiting for services to start..."
sleep 30

# Install/update dependencies
docker compose -f docker-compose.prod.yml exec -T app composer install --no-dev --optimize-autoloader
docker compose -f docker-compose.prod.yml exec -T app npm ci
docker compose -f docker-compose.prod.yml exec -T app npm run build

# Laravel optimizations
docker compose -f docker-compose.prod.yml exec -T app php artisan key:generate --force
docker compose -f docker-compose.prod.yml exec -T app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan view:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan storage:link

# Set permissions
docker compose -f docker-compose.prod.yml exec -T app chown -R www-data:www-data /var/www/html/storage
docker compose -f docker-compose.prod.yml exec -T app chown -R www-data:www-data /var/www/html/bootstrap/cache

echo "✅ Deployment complete!"
