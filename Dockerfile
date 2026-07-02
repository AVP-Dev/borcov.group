FROM php:8.4-fpm-alpine

# Install system dependencies (keep only runtime necessities)
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    nginx \
    supervisor \
    openssl

# Install php-extension-installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install PHP extensions using the installer (highly optimized, handles deps & cleanup)
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    intl \
    opcache

# Install zip extension using official docker-php-ext-install (more reliable on aarch64)
RUN apk add --no-cache libzip-dev && \
    docker-php-ext-install zip && \
    apk del libzip-dev

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
