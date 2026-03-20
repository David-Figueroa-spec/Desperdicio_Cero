FROM php:8.2-apache

# 1. Instalar dependencias del sistema para PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev

# 2. Instalar y habilitar las extensiones de PHP
RUN docker-php-ext-install pgsql pdo_pgsql

# 3. Copiar el código del proyecto
COPY . /var/www/html/

EXPOSE 80