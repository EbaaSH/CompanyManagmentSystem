FROM php:8.5-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    ca-certificates

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    bcmath \
    zip \
    intl

RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd

RUN a2enmod rewrite headers

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

COPY apache.conf /etc/apache2/sites-available/000-default.conf

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

RUN chmod -R 775 storage bootstrap/cache

RUN chmod +x docker-entrypoint.sh

EXPOSE 80

CMD ["sh", "docker-entrypoint.sh"]