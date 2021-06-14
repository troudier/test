FROM php:7.4-apache

RUN apt-get update \
    &&  apt-get install -y --no-install-recommends \
        locales apt-utils git libicu-dev g++ libpng-dev libxml2-dev libzip-dev libonig-dev libxslt-dev unzip \
\
    &&  echo "en_US.UTF-8 UTF-8" > /etc/locale.gen  \
    &&  echo "fr_FR.UTF-8 UTF-8" >> /etc/locale.gen \
    &&  locale-gen \
\   
    &&  docker-php-ext-configure \
            intl \
    &&  docker-php-ext-install \
            pdo pdo_mysql opcache intl zip calendar dom mbstring gd xsl \
\
    &&  pecl install apcu && docker-php-ext-enable apcu

COPY . /var/www 

RUN mkdir -p /var/www/var/cache /var/www/var/log && chmod 777 -R /var/www/var/cache /var/www/var/log

RUN mv /var/www/docker/vhosts/vhosts.conf /etc/apache2/sites-enabled/site.conf

WORKDIR /var/www/