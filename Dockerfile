FROM php:8.2-apache

# Install system dependencies and required PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql gd zip bcmath \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Point Apache DocumentRoot to /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure Apache directory access for NOIR public folder
RUN echo '<Directory /var/www/html/public/>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/noir.conf \
    && a2enconf noir

# Copy project files
WORKDIR /var/www/html
COPY . /var/www/html

# Ensure write permissions for storage and public uploads
RUN mkdir -p /var/www/html/storage/cache \
             /var/www/html/storage/logs \
             /var/www/html/storage/sessions \
             /var/www/html/storage/uploads \
             /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/storage /var/www/html/public/uploads

# Render routes HTTP traffic to port 80
EXPOSE 80

CMD ["apache2-foreground"]
