FROM php:8.2-apache

# Enable Apache rewrite and headers modules
RUN a2enmod rewrite headers

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# DocumentRoot containing "&" must be QUOTED
ENV APACHE_DOCUMENT_ROOT="/var/www/html/Lost&found/htdocs/public"

# Update Apache config for DocumentRoot
RUN sed -ri "s#DocumentRoot /var/www/html#DocumentRoot ${APACHE_DOCUMENT_ROOT}#g" /etc/apache2/sites-available/000-default.conf \
    && sed -ri "s#<Directory /var/www/html>#<Directory ${APACHE_DOCUMENT_ROOT}>#g" /etc/apache2/sites-available/000-default.conf \
    && sed -ri "s#/var/www/html#${APACHE_DOCUMENT_ROOT}#g" /etc/apache2/apache2.conf

# Copy project files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/Lost\&found/htdocs/assets/uploads

EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
