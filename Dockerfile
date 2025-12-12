# Use the official php image with apache pre-installed
FROM php:8.2-apache

# Avoid interactive prompts in Debian
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies (added: libicu-dev, libfreetype6-dev, zlib1g-dev, libonig-dev)
# Configure GD and install PHP extensions (intl needs libicu-dev, mbstring needs libonig-dev)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip zip curl \
    libpq-dev libzip-dev zlib1g-dev \
    libjpeg-dev libpng-dev libfreetype6-dev \
    libxml2-dev libicu-dev \
    libonig-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install -j"$(nproc)" \
    pdo pdo_pgsql zip mbstring gd bcmath soap intl opcache \
    pcntl \
 && rm -rf /var/lib/apt/lists/*

# Enable the Apache rewrite module
RUN a2enmod rewrite

# Configure Apache to serve from Laravel public directory
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy Laravel app
COPY . .

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Laravel dependencies
RUN git config --global --add safe.directory /var/www/html \
 && composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Make sure storage paths exist and set safe permissions
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data /var/www/html \
 && chmod -R ug+rw storage bootstrap/cache \
 && touch storage/logs/laravel.log \
 && chmod 664 storage/logs/laravel.log

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
