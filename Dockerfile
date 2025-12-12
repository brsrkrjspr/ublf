FROM php:8.2-apache

# Enable Apache rewrite and headers modules
RUN a2enmod rewrite headers

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy Apache configuration file first (before project files)
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copy project files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 "/var/www/html/Lost&found/htdocs/assets/uploads"

EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
