FROM php:8.4-apache

# Install SQLite and Intl extensions
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libicu-dev \
    && docker-php-ext-install pdo pdo_sqlite intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Set permissions for data directory
RUN mkdir -p /var/www/data && chown -R www-data:www-data /var/www/data
