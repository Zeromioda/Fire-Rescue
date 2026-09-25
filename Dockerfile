# Multi-stage Dockerfile for Laravel app
# - Stage 1: build frontend assets with Node 20
# - Stage 2: run PHP and serve the app on $PORT (set by Railway, defaults to 3000)

FROM node:20 AS node_builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources resources
RUN npm run build

FROM php:8.2-cli
WORKDIR /var/www/html

# System dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    curl \
    zip \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip gd bcmath \
    && rm -rf /var/lib/apt/lists/*

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

# Run migrations, seed roles/admin, cache config, then serve on Railway's $PORT
CMD php artisan migrate --force \
    && php artisan db:seed --class=AdminSeeder --force \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan serve --host=0.0.0.0 --port=$PORT
