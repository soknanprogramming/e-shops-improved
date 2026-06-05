FROM php:8.2-apache

# Install MySQL PDO
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Enable Apache rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Create uploads directories and set permissions
RUN mkdir -p uploads/products uploads/categories uploads/profiles \
    && chown -R www-data:www-data uploads \
    && chmod -R 775 uploads