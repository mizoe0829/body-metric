# Stage 1: Build Frontend Assets
FROM node:22-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: PHP Application Runner
FROM php:8.2-cli-alpine
WORKDIR /var/www/html

# Install system dependencies & PHP extensions for Laravel + SQLite
RUN apk add --no-cache \
    sqlite-libs \
    sqlite-dev \
    curl \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_sqlite mbstring bcmath zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .
COPY --from=frontend-builder /app/public/build ./public/build

# Install PHP production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Prepare storage & cache permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]

