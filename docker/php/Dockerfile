FROM php:8.3-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && docker-php-ext-enable pdo_mysql

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
ARG WWWGROUP=1000
ARG WWWUSER=1000

RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u $WWWUSER sail

# Copy custom PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-sail.ini

# Set proper permissions
RUN chown -R sail:sail /var/www/html

# Switch to sail user
USER sail

# Expose port 9000 for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
