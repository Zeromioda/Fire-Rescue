# Multi-stage Dockerfile for Laravel app
# - Stage 1: build frontend assets with Node 20
# - Stage 2: serve the app with FrankenPHP on $PORT (set by Railway, defaults to 3000)

FROM node:20 AS node_builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources resources
RUN npm run build

# FrankenPHP (Caddy + PHP) serves many requests in parallel, serves static
# files directly with compression, and runs PHP with OPcache enabled.
FROM dunglas/frankenphp:1-php8.2-bookworm
WORKDIR /var/www/html

# System tools and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_mysql pdo_pgsql zip gd bcmath opcache

# Production PHP settings (code never changes inside the container)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'realpath_cache_size=4096K'; \
        echo 'realpath_cache_ttl=600'; \
        echo 'memory_limit=256M'; \
        echo 'upload_max_filesize=20M'; \
        echo 'post_max_size=20M'; \
    } > "$PHP_INI_DIR/conf.d/zz-app.ini"

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy app sources
COPY . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-progress

# Copy built frontend into public (Laravel Vite writes here)
COPY --from=node_builder /app/public/build /var/www/html/public/build

# Permissions for Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

ENV PORT=3000
EXPOSE 3000

# Run migrations, seed roles/admin, warm Laravel caches, then serve on Railway's $PORT
CMD php artisan migrate --force \
    && php artisan db:seed --class=AdminSeeder --force \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache \
    && exec frankenphp run --config /var/www/html/Caddyfile
