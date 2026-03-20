FROM php:8.2-apache

# 1. Instalar dependencias del sistema necesarias
# Incluimos librerías para Postgres, manejo de imágenes (GD), 
# compresión (Zip) y manejo de strings/multibyte (mbstring)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# 2. Configurar e instalar extensiones de PHP
# - pdo_pgsql/pgsql: Para tu base de datos en Supabase
# - gd: Para procesar imágenes (si llegas a subir fotos de donaciones)
# - intl: Para internacionalización y formatos de moneda/fecha
# - mbstring: Vital para que PHP maneje bien las tildes y la "ñ"
# - opcache: Para que el sistema vuele en producción
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pgsql \
    pdo_pgsql \
    gd \
    zip \
    intl \
    mbstring \
    bcmath \
    opcache

# 3. Habilitar mod_rewrite de Apache
# Esto es fundamental si usas archivos .htaccess o quieres rutas amigables (SEO)
RUN a2enmod rewrite

# 4. Copiar el código del proyecto
COPY . /var/www/html/

# Ajustar permisos para que Apache pueda leer/escribir si es necesario
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]