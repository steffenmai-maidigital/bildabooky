# BilDaBooky — Aufbau nach dem LinkHub-/CrewGate-Muster
# BilDaBooky — structure following the LinkHub/CrewGate pattern
FROM php:8.4-apache

# Systempakete und PHP-Erweiterungen (SQLite, Zip für Sicherungen, LDAP für
# die optionale Anmeldung über das Schulverzeichnis)
# System packages and PHP extensions (SQLite, Zip for backups, LDAP for
# the optional sign-in via the school directory)
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libsqlite3-dev libldap2-dev pkg-config unzip git sqlite3 \
    && docker-php-ext-configure ldap \
    && docker-php-ext-install pdo_sqlite zip ldap \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Größere Uploads (Fotos, Tonaufnahmen)
# Larger uploads (photos, sound recordings)
COPY docker/php.ini /usr/local/etc/php/conf.d/bildabooky.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && printf 'ServerName localhost\nServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-enabled/zz-bildabooky.conf

WORKDIR /var/www/html

# Erst nur composer.json kopieren, damit der Layer gecacht wird
# Copy only composer.json first so the layer gets cached
COPY composer.json ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader

# Restliche Anwendung (Leserechte unabhängig vom Dateisystem des Bauhosts)
# Rest of the application (read permissions independent of the build host's file system)
COPY . .
RUN chmod -R a+rX /var/www/html \
    && composer dump-autoload --optimize \
    && mkdir -p /data /backups \
    && chown -R www-data:www-data storage bootstrap/cache /data /backups

# Versionsnummer (JJMMTTNN) als Kennzeichnung des Abbilds — erst hier,
# damit der Zwischenspeicher der Schichten davor erhalten bleibt
# Version number (YYMMDDNN) as the image label — only here,
# so the cache of the layers before it is kept
ARG VERSION=dev
LABEL org.opencontainers.image.title="BilDaBooky" \
      org.opencontainers.image.version="${VERSION}" \
      org.opencontainers.image.licenses="AGPL-3.0-or-later"
# Die angezeigte Version (Fußzeile, Hilfe) ist immer die des Abbilds:
# eine gesetzte Nummer gewinnt gegen den Stand in config/version.php
# The displayed version (footer, help) is always that of the image:
# a given number wins over the value in config/version.php
RUN if [ "$VERSION" != "dev" ]; then \
        sed -ri "s/('number'\s*=>\s*)'[^']*'/\1'${VERSION}'/" config/version.php \
        && sed -ri "s/('build'\s*=>\s*)'[^']*'/\1'$(TZ=Europe/Berlin date +%y%m%d%H%M)'/" config/version.php; \
    fi

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
