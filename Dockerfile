FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    unzip \
    && docker-php-ext-install pdo pdo_mysql mysqli zip gd curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2dissite 000-default.conf || true
RUN a2ensite 000-default.conf

COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

COPY . /var/www/html/

RUN rm -rf /var/www/html/.git /var/www/html/docker /var/www/html/Dockerfile /var/www/html/render.yaml /var/www/html/.gitignore 2>/dev/null || true

RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

RUN mkdir -p /var/www/html/uploads/permisos /var/www/html/uploads/usuarios /var/www/html/uploads/acogida /var/www/html/uploads/seguimientos /var/www/html/uploads/actualizaciones /var/www/html/uploads/solicitudes_protectora \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80
