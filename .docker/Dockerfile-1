FROM php:7.4.1-apache

USER root

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
        libpng-dev \
        zlib1g-dev \
        libxml2-dev \
        libzip-dev \
        libonig-dev \
        zip \
        curl \
        unzip \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-install mysqli \
    && docker-php-ext-install zip \
    && docker-php-source delete

RUN composer install --no-scripts --no-dev --no-autoloader

COPY . .

RUN echo 'chown -R www-data:www-data /var/www/ \

&& composer dump-autoload \

&& cp .env.example .env \

&& php artisan key:generate \

&& php artisan migrate \

&& php artisan db:seed \

&& apachectl -D FOREGROUND' >>/root/container_init.sh && \

 chmod 755/root/container_init.sh

