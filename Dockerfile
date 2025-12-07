# Use official PHP image with Apache
FROM php:8.2-apache

# Install PHP extensions required by WordPress
RUN apt-get update && apt-get install -y \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        zip unzip \
    && docker-php-ext-install mysqli gd
    && docker-php-ext-enable mysqli gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy WordPress files into Apache root
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Give proper permissions
RUN chown -R www-data:www-data /var/www/html

# Expose default port
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
