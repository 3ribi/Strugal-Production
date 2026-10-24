# syntax=docker/dockerfile:1

# 1) Dépendances PHP
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# 2) Compilation des assets (Tailwind / Vite) — a besoin de vendor/ pour le CSS de Flux
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=vendor /app/vendor ./vendor
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# 3) Image finale
FROM php:8.3-apache
ADD --chmod=0755 https://github.com/mlocati/docker-php-extensions/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions gd zip intl bcmath gmp pdo_sqlite opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && rm /usr/bin/composer \
    && cp -r storage /opt/storage-seed \
    && mkdir -p bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr
EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
