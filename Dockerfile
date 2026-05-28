FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive
# Prevent composer from hitting the 512 MB Render build RAM cap
ENV COMPOSER_MEMORY_LIMIT=-1

# ── PHP 8.2 + all extensions required by Laravel + MongoDB ──────────────────
# ext-ctype, filter, hash, iconv, json, phar, session, tokenizer → php8.2-common
# ext-dom, libxml, xml, xmlwriter                               → php8.2-xml
# ext-curl                                                       → php8.2-curl
# ext-fileinfo                                                   → php8.2-fileinfo
# ext-mbstring                                                   → php8.2-mbstring
# ext-mongodb                                                    → php8.2-mongodb
# ext-zip                                                        → php8.2-zip
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

# Fail fast if any critical extension is missing (shows the name in build logs)
RUN php -r "
  \$need = ['mongodb','mbstring','curl','dom','xml','fileinfo','ctype','tokenizer','json'];
  \$miss = array_filter(\$need, fn(\$e) => !extension_loaded(\$e));
  if (\$miss) { echo 'MISSING extensions: '.implode(', ',\$miss).PHP_EOL; exit(1); }
  echo 'All extensions OK'.PHP_EOL;
"

# ── Node.js 20 ───────────────────────────────────────────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
 && apt-get install -y nodejs \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

# ── Composer ─────────────────────────────────────────────────────────────────
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
