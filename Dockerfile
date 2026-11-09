FROM php:8.5-apache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev unzip \
    && docker-php-ext-install intl pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY docker/restaurant.conf /etc/apache2/sites-available/000-default.conf
COPY . .
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist --no-scripts \
    && php bin/console assets:install public \
    && mkdir -p var/cache var/log \
    && chown -R www-data:www-data var

ENTRYPOINT ["sh", "docker/start.sh"]
CMD ["apache2-foreground"]
