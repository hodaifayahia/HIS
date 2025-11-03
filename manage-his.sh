#!/bin/bash

case $1 in
    "start")
        echo "🚀 Starting HIS Application..."
        ./vendor/bin/sail up -d
        echo "⏳ Waiting for services to be ready..."
        sleep 3
        echo "🎨 Starting Vite dev server with HMR..."
        docker exec -d his-php-1 bash -c "cd /var/www/html && npm run dev -- --host 0.0.0.0 > /dev/null 2>&1 &"
        sleep 2
        echo "✅ All services started!"
        echo "📱 Application: http://localhost:9000"
        echo "🔄 Vite HMR: http://localhost:5173"
        echo "💾 Frontend changes will auto-reload (no build needed)!"
        ;;
    "stop")
        echo "⏹️ Stopping HIS Application..."
        echo "🛑 Stopping Vite dev server..."
        docker exec his-php-1 pkill -f "vite" 2>/dev/null || true
        ./vendor/bin/sail down
        ;;
    "restart")
        echo "🔄 Restarting HIS Application..."
        ./vendor/bin/sail restart
        ;;
    "status")
        ./health-check.sh
        ;;
    "logs")
        echo "📋 Application Logs:"
        docker exec -it his-php-1 tail -f storage/logs/laravel.log
        ;;
    "shell")
        echo "🖥️ Accessing application shell..."
        docker exec -it his-php-1 bash
        ;;
    "migrate")
        echo "📊 Running migrations..."
        docker exec -it his-php-1 php artisan migrate
        ;;
    "build")
        echo "🎨 Building frontend assets..."
        docker exec -it his-php-1 npm run build
        ;;
    "dev")
        echo "🎨 Starting frontend dev server..."
        docker exec -it his-php-1 npm run dev
        ;;
    "dev-bg")
        echo "🎨 Starting frontend dev server in background..."
        docker exec -d his-php-1 npm run dev
        ;;
    *)
        echo "HIS Application Management"
        echo "Usage: ./manage-his.sh [command]"
        echo ""
        echo "Commands:"
        echo "  start    - Start the application"
        echo "  stop     - Stop the application"
        echo "  restart  - Restart the application"
        echo "  status   - Check application health"
        echo "  logs     - View application logs"
        echo "  shell    - Access application shell"
        echo "  migrate  - Run database migrations"
        echo "  build    - Build frontend assets"
        echo "  dev      - Start frontend dev server (interactive)"
        echo "  dev-bg   - Start frontend dev server (background)"
        ;;
esac
