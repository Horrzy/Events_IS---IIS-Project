# PHP 8.1 + Apache odpovídá prostředí na školním serveru Eva
FROM php:8.1-apache

# Systémové knihovny a PHP rozšíření pro MySQL, obrázky (GD) a archivy
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Apache musí směřovat do složky public/, ne do kořene projektu
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's#/var/www/html#${APACHE_DOCUMENT_ROOT}#g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
