FROM node:24-alpine AS assets
WORKDIR /src
COPY package*.json ./
RUN npm ci --no-audit --no-fund
COPY resources resources
COPY vite.config.ts tsconfig.json ./
RUN npx vite build

FROM php:8.4-fpm-alpine AS app
RUN apk add --no-cache icu-libs libpq oniguruma libzip && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev postgresql-dev oniguruma-dev libzip-dev && docker-php-ext-install pdo_pgsql mbstring intl zip opcache && apk del .build-deps
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader && chown -R www-data:www-data storage bootstrap/cache
COPY --from=assets /src/public/build public/build
RUN printf 'date.timezone=America/Bogota\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/siac.ini
USER www-data
EXPOSE 9000
CMD ["php-fpm"]

FROM nginx:1.28-alpine AS web
COPY deploy/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
