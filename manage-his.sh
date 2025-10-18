#!/bin/bash

case $1 in
    "start")
        echo "🚀 Starting HIS Application..."
        ./vendor/bin/sail up -d
        ;;
    "stop")
        echo "⏹️ Stopping HIS Application..."
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
        docker exec -it his-vite-1 npm run build
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
        ;;
esac
