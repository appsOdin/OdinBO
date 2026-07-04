# ─────────────────────────────────────────────────────────────
# OdinBO – Frontend PHP / Apache
# ─────────────────────────────────────────────────────────────
FROM php:8.2-apache

# ── System dependencies ──────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl \
        libcurl4-openssl-dev \
        libssl-dev \
        libonig-dev \
        libxml2-dev \
        zip \
        unzip \
    && docker-php-ext-install \
        curl \
        mbstring \
        xml \
        fileinfo \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# ── Apache virtual host ──────────────────────────────────────
COPY .docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# ── PHP config ───────────────────────────────────────────────
COPY .docker/php/php.ini /usr/local/etc/php/conf.d/odinbo.ini

# ── Application files ────────────────────────────────────────
WORKDIR /var/www/html
COPY . .

# ── Storage directories (sessions & logs) ────────────────────
RUN mkdir -p storage/sessions storage/logs storage/signatures \
    && chown -R www-data:www-data storage \
    && chmod -R 775 storage

# ── Fix permissions for the whole app ────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;

EXPOSE 80
