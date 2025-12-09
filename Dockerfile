FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libsqlite3-dev \
    libicu-dev \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo \
        pdo_sqlite \
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

# Copy all application code
COPY . .

# Create necessary directories before composer install
RUN mkdir -p web/sites/default/files \
    && mkdir -p private \
    && mkdir -p config/sync \
    && mkdir -p /var/www/html/data \
    && mkdir -p keys

# Install dependencies with memory optimizations for free tier
# COMPOSER_MEMORY_LIMIT=-1 removes memory limit
# --prefer-dist uses zip archives instead of git clones (faster, less memory)
# --no-progress reduces output overhead
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress

# Copy settings.php after scaffold
RUN cp assets/settings.php web/sites/default/settings.php

# Create SQLite database file
RUN touch /var/www/html/data/drupal.sqlite

# Set permissions
RUN chown -R www-data:www-data web/sites/default \
    && chown -R www-data:www-data private \
    && chown -R www-data:www-data /var/www/html/data \
    && chown -R www-data:www-data keys \
    && chmod -R 755 web/sites/default/files \
    && chmod -R 755 private \
    && chmod -R 755 /var/www/html/data

# Environment variables for Drupal
ENV DRUPAL_SETTINGS_PATH=/var/www/html/web/sites/default/settings.php

EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=60s \
    CMD curl -f http://localhost/api || exit 1

CMD ["apache2-foreground"]
