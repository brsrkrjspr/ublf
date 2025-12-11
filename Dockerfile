FROM php:8.2-apache

# Enable Apache rewrite (important for routing)
RUN a2enmod rewrite

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set DocumentRoot (with quotes if folder contains "&")
ENV APACHE_DOCUMENT_ROOT="/var/www/html/Lost&found/htdocs/public"

# Update Apache config to use custom DocumentRoot
RUN sed -ri "s#DocumentRoot /var/www/html#DocumentRoot ${APACHE_DOCUMENT_ROOT}#g" /etc/apache2/sites-available/000-default.conf \
    && sed -ri "s#/var/www/html#${APACHE_DOCUMENT_ROOT}#g" /etc/apache2/apache2.conf

# Copy project files
COPY . /var/www/html/

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
