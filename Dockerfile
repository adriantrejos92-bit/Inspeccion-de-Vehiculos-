FROM php:8.2-apache

# Instalar dependencias del sistema y extensiones PDO + zip (para PhpSpreadsheet)
RUN apt-get update && apt-get install -y libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar archivos del proyecto (incluyendo vendor/)
COPY . /var/www/html/

# Permisos
RUN chown -R www-data:www-data /var/www/html

# Render usa la variable PORT
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

CMD ["apache2-foreground"]
