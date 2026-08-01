FROM php:8.4-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) pdo_mysql mbstring bcmath gd opcache \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

COPY . .

RUN rm -f bootstrap/cache/*.php \
 && composer dump-autoload \
    --classmap-authoritative \
    --no-interaction \
 && mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

COPY docker/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

EXPOSE 8000

CMD ["sh", "-c", "mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && chmod -R 777 storage bootstrap/cache && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear && php artisan migrate --force && php artisan db:seed --force && php artisan admin:crear-demo && php artisan cache:clear && export PHP_CLI_SERVER_WORKERS=6 && exec php artisan serve --host=0.0.0.0 --port=8000"]
