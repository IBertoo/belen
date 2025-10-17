FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install pdo pdo_mysql

# Activar mod_rewrite (opcional)
RUN a2enmod rewrite

# Configurar DocumentRoot (ya es /var/www/html por defecto)
WORKDIR /var/www/html

# Copiar un php.ini de producción mínimo (opcional)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" || true
# Habilitar gd
RUN apt-get update && \
    apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd 
# Configurar límites de carga más altos
RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini



