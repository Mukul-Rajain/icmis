FROM php:8.2-cli-alpine

# Build deps for PHP extensions
RUN apk add --no-cache \
    autoconf gcc g++ make \
    oniguruma-dev libxml2-dev openssl-dev pcre-dev \
    nodejs npm git

# MongoDB PHP extension (PECL)
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Standard PHP extensions Laravel needs
RUN docker-php-ext-install mbstring xml opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# PHP deps — layer-cached separately from app code
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Node deps — layer-cached separately
COPY package.json package-lock.json ./
RUN npm ci

# Full application (including storage/app/seeded/)
COPY . .

# Build React/Vite assets
RUN npm run build && npm prune --production

# Ensure writable dirs exist with correct permissions
RUN mkdir -p storage/logs \
             storage/framework/cache \
             storage/framework/sessions \
             storage/framework/views \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# At startup: cache routes+views then serve
CMD ["sh", "-c", "php artisan storage:link --no-interaction 2>/dev/null; php artisan route:cache; php artisan view:cache; php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
