# Production image for Render (or any Docker host): PHP 8.3 + Apache.
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libicu-dev libzip-dev unzip git \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pdo_mysql intl zip bcmath opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/kakeibo.ini

WORKDIR /var/www/html

# Install dependencies first so this layer is cached between deploys.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/start.sh

# Render injects PORT (default 10000); Apache reads it from the environment.
ENV PORT=10000 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr
RUN sed -i 's/^Listen 80$/Listen ${PORT}/' /etc/apache2/ports.conf

EXPOSE 10000
CMD ["docker/start.sh"]
