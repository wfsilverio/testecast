FROM php:8.2-fpm-alpine
RUN apk add --no-cache git unzip libpq-dev bash \
    && docker-php-ext-install pdo pdo_pgsql bcmath
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
# Copy only composer files first to leverage layer caching
COPY app/composer.json /var/www/html/composer.json
RUN composer install --no-interaction --prefer-dist
# Copy the rest of the application
COPY app/ /var/www/html/
EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
