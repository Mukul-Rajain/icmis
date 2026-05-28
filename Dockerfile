FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
ENV COMPOSER_MEMORY_LIMIT=-1

# Cache-bust arg — increment to force a clean apt layer on Render
ARG CACHE_BUST=2

# ── PHP 8.2 + all Laravel-required extensions via Ondrej PPA ─────────────────
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
      ca-certificates gnupg software-properties-common curl git unzip \
 && add-apt-repository -y ppa:ondrej/php \
 && apt-get update \
 && apt-get install -y --no-install-recommends \
      php8.2-cli \
      php8.2-common \
      php8.2-curl \
      php8.2-fileinfo \
      php8.2-mbstring \
      php8.2-mongodb \
      php8.2-opcache \
      php8.2-xml \
      php8.2-zip \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# ── Node.js 20 ───────────────────────────────────────────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# ── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# PHP deps
# --ignore-platform-req=ext-mongodb: the extension INI is wired up by apt but
# Composer's build-time platform probe can't dlopen it in this env. The .so
# IS present and loads fine at runtime (php artisan boots correctly).
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction \
      --ignore-platform-req=ext-mongodb

# Node deps
COPY package.json package-lock.json ./
RUN npm ci

# Application source
COPY . .
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
