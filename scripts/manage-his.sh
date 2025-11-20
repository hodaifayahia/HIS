#!/bin/bash

case $1 in
    "start")
        echo "🚀 Starting HIS Application..."
        ./vendor/bin/sail up -d
        echo "⏳ Waiting for services to be ready..."
        sleep 3
        echo "🎨 Starting Vite dev server with HMR..."
        docker exec -d his-laravel.test-1 bash -c "cd /var/www/html && npm run dev -- --host 0.0.0.0 > /dev/null 2>&1 &"
        sleep 2
        echo "✅ All services started!"
        echo "📱 Application: http://localhost:9000"
        echo "🔄 Vite HMR: http://localhost:5173"
        echo "💾 Frontend changes will auto-reload (no build needed)!"
        ;;
    "stop")
        echo "⏹️ Stopping HIS Application..."
        echo "🛑 Stopping Vite dev server..."
        docker exec his-laravel.test-1 pkill -f "vite" 2>/dev/null || true
        ./vendor/bin/sail down
        ;;
    "restart")
        echo "🔄 Restarting HIS Application..."
        
        # Stop frontend and queue processes
        echo "🛑 Stopping frontend and queue workers..."
        docker exec his-laravel.test-1 pkill -f "vite" 2>/dev/null || true
        docker exec his-laravel.test-1 pkill -f "queue:work" 2>/dev/null || true
        docker exec his-laravel.test-1 pkill -f "reverb:start" 2>/dev/null || true
        
        # Restart Docker containers
        ./vendor/bin/sail restart
        
        # Wait for containers to be ready
        echo "⏳ Waiting for containers to be ready..."
        sleep 5
        
        # Start frontend dev server
        echo "🎨 Starting Vite dev server..."
        docker exec -d his-laravel.test-1 bash -c "cd /var/www/html && npm run dev -- --host 0.0.0.0 > /var/www/html/storage/logs/vite.log 2>&1 &"
        sleep 2
        
        # Start queue worker
        echo "⚙️ Starting queue worker..."
        docker exec -d his-laravel.test-1 bash -c "cd /var/www/html && php artisan queue:work --timeout=0 > /var/www/html/storage/logs/queue.log 2>&1 &"
        
        # Start Reverb WebSocket server
        echo "🔌 Starting Reverb WebSocket server..."
        docker exec -d his-laravel.test-1 bash -c "cd /var/www/html && php artisan reverb:start --port=5207 > /var/www/html/storage/logs/reverb.log 2>&1 &"
        
        echo "✅ All services restarted!"
        echo "📱 Application: http://localhost:8080"
        echo "🔄 Vite HMR: http://localhost:5173"
        echo "🔌 Reverb: ws://localhost:5207"
        ;;
    "status")
        ./health-check.sh
        ;;
    "logs")
        echo "📋 Application Logs:"
        docker exec -it his-laravel.test-1 tail -f storage/logs/laravel.log
        ;;
    "shell")
        echo "🖥️ Accessing application shell..."
        docker exec -it his-laravel.test-1 bash
        ;;
    "migrate")
        echo "📊 Running migrations..."
        docker exec -it his-laravel.test-1 php artisan migrate
        ;;
    "seed")
        if [ -z "$2" ]; then
            echo "🌱 Seeding database (all seeders)..."
            docker exec -it his-laravel.test-1 php artisan db:seed
        else
            echo "🌱 Seeding database with $2..."
            docker exec -it his-laravel.test-1 php artisan db:seed --class="$2"
        fi
        ;;
    "fresh")
        echo "🔄 Fresh migration with seeding..."
        docker exec -it his-laravel.test-1 php artisan migrate:fresh --seed
        ;;
    "build")
        echo "🎨 Building frontend assets..."
        docker exec -it his-laravel.test-1 npm run build
        ;;
    "dev")
        echo "🎨 Starting frontend dev server..."
        docker exec -it his-laravel.test-1 npm run dev
        ;;
    "dev-bg")
        echo "🎨 Starting frontend dev server in background..."
        docker exec -d his-laravel.test-1 npm run dev
        ;;
    "full-dev")
        echo "🚀 Starting Full Development Stack..."
        
        # Start Docker containers
        echo "🐳 Starting Docker containers..."
        ./vendor/bin/sail up -d
        
        # Wait for services
        echo "⏳ Waiting for services to be ready..."
        sleep 5
        
        # Start Vite
        echo "🎨 Starting Vite dev server..."
        docker exec -d his-laravel.test-1 bash -c "cd /var/www/html && npm run dev -- --host 0.0.0.0 > /var/www/html/storage/logs/vite.log 2>&1 &"
        sleep 2
        
        # Start Queue
        echo "⚙️ Starting queue worker..."
        docker exec -d his-laravel.test-1 bash -c "cd /var/www/html && php artisan queue:work --timeout=0 > /var/www/html/storage/logs/queue.log 2>&1 &"
        
        # Start Reverb
        echo "🔌 Starting Reverb WebSocket server..."
        docker exec -d his-laravel.test-1 bash -c "cd /var/www/html && php artisan reverb:start --port=5207 > /var/www/html/storage/logs/reverb.log 2>&1 &"
        
        # Start Laravel Test (if exists)
        if docker exec his-laravel.test-1 test -f "artisan"; then
            echo "🧪 Starting Laravel test watcher (optional)..."
            # Uncomment if you want automatic test running:
            # docker exec -d his-laravel.test-1 bash -c "cd /var/www/html && php artisan test --watch > /var/www/html/storage/logs/tests.log 2>&1 &"
        fi
        
        echo ""
        echo "✅ Full development stack started!"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "📱 Application:  http://localhost:8080"
        echo "🔄 Vite HMR:     http://localhost:5173"
        echo "🔌 Reverb:       ws://localhost:5207"
        echo "⚙️  Queue:        Running in background"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo ""
        echo "📋 Logs:"
        echo "  Vite:   docker exec his-laravel.test-1 tail -f storage/logs/vite.log"
        echo "  Queue:  docker exec his-laravel.test-1 tail -f storage/logs/queue.log"
        echo "  Reverb: docker exec his-laravel.test-1 tail -f storage/logs/reverb.log"
        echo "  Laravel: docker exec his-laravel.test-1 tail -f storage/logs/laravel.log"
        ;;
    "test")
        echo "🧪 Running Laravel tests..."
        docker exec -it his-laravel.test-1 php artisan test
        ;;
    "test-watch")
        echo "🧪 Running Laravel tests in watch mode..."
        docker exec -it his-laravel.test-1 php artisan test --watch
        ;;
    *)
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "🏥 HIS Application Management"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "Usage: ./manage-his.sh [command]"
        echo ""
        echo "📦 Container Management:"
        echo "  start      - Start Docker containers only"
        echo "  stop       - Stop all services"
        echo "  restart    - Restart containers + frontend + queue + reverb"
        echo "  status     - Check application health"
        echo ""
        echo "🚀 Development:"
        echo "  full-dev   - Start FULL stack (containers + vite + queue + reverb)"
        echo "  dev        - Start frontend dev server (interactive)"
        echo "  dev-bg     - Start frontend dev server (background)"
        echo "  build      - Build frontend assets for production"
        echo ""
        echo "🧪 Testing:"
        echo "  test       - Run all Laravel tests"
        echo "  test-watch - Run tests in watch mode (auto-rerun on changes)"
        echo ""
        echo "🛠️ Utilities:"
        echo "  shell      - Access application shell"
        echo "  logs       - View Laravel application logs"
        echo "  migrate    - Run database migrations"
        echo "  seed       - Seed database (all seeders)"
        echo "  seed <SeederName> - Seed specific seeder"
        echo "  fresh      - Fresh migration + seed"
        echo ""
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "💡 Quick Start: ./manage-his.sh full-dev"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        ;;
esac
