FROM php:7.3.7-fpm

RUN apt-get update && apt-get install -y \
    curl \
    libzip-dev \
    zip \
    git \
&& docker-php-ext-configure zip --with-libzip \
&& docker-php-ext-install \
    zip \
    pdo_mysql \
&& pecl install \
    xdebug-2.8.0beta1 \
&& docker-php-ext-enable \
    xdebug

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD ["php-fpm"]