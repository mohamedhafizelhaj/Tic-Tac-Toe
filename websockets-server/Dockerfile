FROM php:8.1-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    supervisor \
    && docker-php-ext-install zip pdo pdo_mysql

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY laravel-app/composer.json laravel-app/composer.lock ./

RUN composer install --no-scripts --no-autoloader

COPY laravel-app .

RUN composer dump-autoload

RUN chown -R www-data:www-data storage bootstrap/cache

COPY apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY apache/laravel-api.conf /etc/apache2/sites-available/laravel-api.conf

RUN a2ensite laravel-api
RUN a2enmod headers rewrite proxy proxy_http

COPY supervisor/websockets.conf /etc/supervisor/conf.d
COPY supervisor/apache2.conf /etc/supervisor/conf.d

EXPOSE 80 8000

CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]