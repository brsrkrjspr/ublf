FROM php:8.2-apache

# Enable required Apache modules
RUN a2enmod rewrite

# Install common PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set Apache DocumentRoot to /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/Lost\&found/htdocs/public

# Update Apache config to use new DocumentRoot
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Copy all project files into container
COPY . /var/www/html/

# Fix permissions
RUN chown -R www-data:www-data /var/www/html
