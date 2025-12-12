# Use the official php image with apache pre-installed
FROM php:8.2-apache

# Avoid interactive prompts in Debian
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies
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

# Configure Apache to serve from Laravel public directory (now in backend/)
RUN echo '<VirtualHost *:80>\n\
    ServerAdmin webmaster@localhost\n\
    DocumentRoot /var/www/html/backend/public\n\
    <Directory /var/www/html/backend/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Set working directory at repo root
WORKDIR /var/www/html

# Copy entire repo into the image (backend/ will be inside here)
COPY . .

# Install Composer (from official composer image)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Now work inside backend for Laravel
WORKDIR /var/www/html/backend

# Install Laravel dependencies (during build — but overridden by volume at runtime)
RUN git config --global --add safe.directory /var/www/html/backend \
 && composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Make sure storage paths exist and set safe permissions (relative to backend/)
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data /var/www/html/backend \
 && chmod -R ug+rw storage bootstrap/cache \
 && touch storage/logs/laravel.log \
 && chmod 664 storage/logs/laravel.log

# Expose port 80
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
