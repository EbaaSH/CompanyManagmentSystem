# Use official PHP 8.5 image with Apache (latest)
FROM php:8.5-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and build tools
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    wget \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions one by one with fallback
RUN docker-php-ext-install pdo pdo_mysql || true && \
    docker-php-ext-install zip || true && \
    docker-php-ext-install bcmath || true && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd || true && \
    docker-php-ext-install opcache || true

# Set PHP configuration
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/app.ini && \
    echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/app.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/app.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/app.ini

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Copy application code
COPY . .

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/storage 2>/dev/null || true && \
    chmod -R 755 /var/www/html/bootstrap/cache 2>/dev/null || true

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
