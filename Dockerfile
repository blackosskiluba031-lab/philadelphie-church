# Dockerfile pour Philadelphie Church sur Render Free
FROM php:8.3-apache

# Installer les dependances systeme requises pour GD, intl, etc.
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

# Configurer Apache : AllowOverride All + VirtualHost correct
COPY apache-philadelphie.conf /etc/apache2/sites-available/000-default.conf
RUN tr -d '\r' < /etc/apache2/sites-available/000-default.conf > /tmp/vhost.tmp \
    && mv /tmp/vhost.tmp /etc/apache2/sites-available/000-default.conf

# Creer et configurer les dossiers d'upload avec permissions www-data
RUN mkdir -p /var/www/html/uploads/ui \
    /var/www/html/uploads/publications \
    /var/www/html/uploads/medias \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Script de demarrage pour le port dynamique Render ($PORT)
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN tr -d '\r' < /usr/local/bin/entrypoint.sh > /usr/local/bin/entrypoint.sh.tmp \
    && mv /usr/local/bin/entrypoint.sh.tmp /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80 10000

CMD ["/usr/local/bin/entrypoint.sh"]
