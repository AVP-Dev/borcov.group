FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    nginx \
    supervisor \
    openssl \
    postgresql-dev \
    icu-dev \
    libzip-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    intl \
    zip \
    mbstring \
    opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install PHP dependencies (no dev)
RUN composer install --no-dev --no-interaction --optimize-autoloader --prefer-dist

# Copy docker config files
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh

RUN chmod +x /entrypoint.sh

# Create necessary directories with proper permissions
RUN mkdir -p \
    /var/www/html/backend/runtime \
    /var/www/html/backend/web/assets \
    /var/www/html/frontend/runtime \
    /var/www/html/frontend/web/assets \
    /var/www/html/console/runtime \
    /var/log/nginx \
    /var/log/supervisor \
    /var/run/nginx && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
