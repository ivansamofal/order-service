FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    git \
    curl \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    rabbitmq-c-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    intl \
    opcache

RUN pecl install amqp && docker-php-ext-enable amqp

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

RUN git config --global url."https://github.com/".insteadOf "git@github.com:"

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize

RUN mkdir -p var \
    && chmod +x bin/console \
    && chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
