# PHP 8.1 with Apache matching the school server environment
FROM php:8.1-apache

# Install system libraries and PHP extensions for MySQL, GD, and zip
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Point Apache document root to public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's#/var/www/html#${APACHE_DOCUMENT_ROOT}#g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
