FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

# ── PHP 8.2 + MongoDB from Ondrej PPA (pre-compiled .deb, zero PECL compilation) ──
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      ca-certificates gnupg software-properties-common curl git unzip \
 && add-apt-repository -y ppa:ondrej/php \
 && apt-get update \
 && apt-get install -y --no-install-recommends \
      php8.2-cli \
      php8.2-curl \
      php8.2-mbstring \
      php8.2-xml \
      php8.2-zip \
      php8.2-mongodb \
      php8.2-opcache \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# ── Node.js 20 ──
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs \
 && rm -rf /var/lib/apt/lists/*

# ── Composer ──
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# PHP deps (layer-cached)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Node deps (layer-cached)
COPY package.json package-lock.json ./
RUN npm ci

# Application source
COPY . .

# Build Vite/React assets
RUN npm run build

# Writable Laravel dirs
RUN mkdir -p storage/logs \
             storage/framework/cache \
             storage/framework/sessions \
             storage/framework/views \
 && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

CMD ["sh", "-c", \
  "php artisan storage:link --no-interaction 2>/dev/null || true && \
   php artisan route:cache 2>/dev/null || true && \
   php artisan view:cache  2>/dev/null || true && \
   php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
