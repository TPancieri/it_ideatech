FROM php:8.4

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

#CMD ["php", "/var/www/html/artisan", "serve", "--host=0.0.0.0", "--port=8000"]
CMD php artisan serve --host=0.0.0.0 --port=8000