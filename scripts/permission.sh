# Fixed bootstrap/cache
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod 777 /var/www/html/bootstrap/cache
chmod 666 /var/www/html/bootstrap/cache/*.php

# Fixed storage directories
chmod -R 777 /var/www/html/storage/framework/cache
chmod -R 777 /var/www/html/storage/framework/sessions
chmod -R 777 /var/www/html/storage/framework/views
chmod -R 777 /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage

# Cleared caches
php artisan view:clear
php artisan optimize:clear