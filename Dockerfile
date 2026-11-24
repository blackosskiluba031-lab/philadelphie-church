# Dockerfile pour Philadelphie Church sur Render Free
FROM php:8.3-apache

# Installer les dépendances système requises pour GD, intl, etc.
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libicu-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        intl \
        opcache \
        zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Activer les modules Apache mod_rewrite et mod_headers
RUN a2enmod rewrite headers

# Copier le code source
WORKDIR /var/www/html
COPY . /var/www/html/

# Créer et configurer les dossiers d'upload avec permissions www-data
RUN mkdir -p /var/www/html/uploads/ui \
    /var/www/html/uploads/publications \
    /var/www/html/uploads/medias \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Configuration du port dynamique pour Render (Render injecte $PORT, par défaut 80 ou 10000)
RUN sed -i 's/Listen 80/Listen ${PORT:-80}/' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:${PORT:-80}>/' /etc/apache2/sites-available/000-default.conf

EXPOSE 80 10000

CMD ["apache2-foreground"]
