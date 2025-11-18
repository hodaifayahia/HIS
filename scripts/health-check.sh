#!/bin/bash

echo "🏥 HIS Application Health Check"
echo "==============================="

echo "🐳 Container Status:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{Ports}}" | grep his-

echo ""
echo "🌐 Application Response:"
curl -s -o /dev/null -w "Status: %{http_code} | Time: %{time_total}s" http://10.47.0.26 && echo " ✅"

echo ""
echo "📊 Database Status:"
docker exec -it his-php-1 php artisan tinker --execute="
try {
    \$count = DB::table('migrations')->count();
    echo 'Migrations: ' . \$count . ' ✅' . PHP_EOL;
} catch(Exception \$e) {
    echo 'Database: ERROR - ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""
echo "🎨 Frontend Assets:"
if [ -d "public/build" ]; then
    echo "Build directory: ✅ EXISTS"
    ls -la public/build/assets/ | wc -l | xargs echo "Asset files:"
else
    echo "Build directory: ❌ MISSING"
fi

echo ""
echo "📧 Services Status:"
curl -s -o /dev/null -w "Mailpit: %{http_code}" http://10.47.0.26:8025 && echo " ✅"
curl -s -o /dev/null -w "Meilisearch: %{http_code}" http://10.47.0.26:7700 && echo " ✅"

echo ""
echo "💾 Storage Permissions:"
docker exec -it his-php-1 test -w storage/logs && echo "Logs writable: ✅" || echo "Logs writable: ❌"
docker exec -it his-php-1 test -w storage/app && echo "App storage writable: ✅" || echo "App storage writable: ❌"
