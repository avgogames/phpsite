# Production image: PHP 8.3 + Apache with the document root at /public.
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers \
    && sed -ri 's#/var/www/html#/var/www/html/public#g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's#AllowOverride None#AllowOverride All#g' /etc/apache2/apache2.conf \
    && echo 'ServerTokens Prod\nServerSignature Off' > /etc/apache2/conf-enabled/hardening.conf

COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
WORKDIR /var/www/html
COPY . .
RUN mkdir -p storage/logs && chown -R www-data:www-data storage

EXPOSE 80
