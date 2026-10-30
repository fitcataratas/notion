FROM php:8.3-apache

# Extensões necessárias: banco de dados (PDO MySQL) e imagens (GD, para fotos e logo)
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libwebp-dev \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install pdo_mysql gd \
 && a2enmod rewrite headers remoteip

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-gestao.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/app
COPY app ./app
COPY public ./public
COPY bin ./bin
COPY database ./database

# storage = anexos, registros e backups (montado como volume no docker-compose)
RUN mkdir -p storage/logs storage/fotos storage/aparencia && chown -R www-data:www-data storage

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
