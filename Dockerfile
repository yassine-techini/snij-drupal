FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        opcache \
        intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Apache
RUN a2enmod rewrite headers expires
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Configure PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/drupal.ini

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application code
COPY . .

# Create necessary directories
RUN mkdir -p web/sites/default/files \
    && mkdir -p private \
    && mkdir -p config/sync

# Set permissions
RUN chown -R www-data:www-data web/sites/default/files \
    && chown -R www-data:www-data private \
    && chmod -R 755 web/sites/default/files \
    && chmod -R 755 private

# Environment variables for Drupal
ENV DRUPAL_SETTINGS_PATH=/var/www/html/web/sites/default/settings.php

EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=60s \
    CMD curl -f http://localhost/api || exit 1

CMD ["apache2-foreground"]
