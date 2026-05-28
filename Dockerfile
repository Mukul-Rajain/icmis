FROM php:8.2-cli

# System deps (Debian-based — far more reliable for PECL compilation)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl unzip \
    libssl-dev libcurl4-openssl-dev \
    libonig-dev libxml2-dev libzip-dev \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# MongoDB PHP extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Core PHP extensions Laravel needs
RUN docker-php-ext-install mbstring xml zip opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# PHP deps (layer-cached)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Node deps (layer-cached)
COPY package.json package-lock.json ./
RUN npm ci

# Application code + storage/app/seeded/
COPY . .

# Build React/Vite frontend assets
RUN npm run build

# Writable storage dirs
RUN mkdir -p storage/logs \
             storage/framework/cache \
             storage/framework/sessions \
             storage/framework/views \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# || true on artisan commands so a cache miss never prevents the server starting
CMD ["sh", "-c", \
  "php artisan storage:link --no-interaction 2>/dev/null || true && \
   php artisan route:cache 2>/dev/null || true && \
   php artisan view:cache  2>/dev/null || true && \
   php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
