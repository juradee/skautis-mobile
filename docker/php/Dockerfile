FROM php:8.4-fpm-alpine

# Runtime libs + build deps for the extensions we need.
RUN apk add --no-cache icu-libs libpq libxml2 libzip git \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev postgresql-dev libxml2-dev libzip-dev linux-headers \
 && docker-php-ext-install -j"$(nproc)" intl soap pdo_pgsql zip opcache \
 && pecl install xdebug && docker-php-ext-enable xdebug \
 && apk del .build-deps && rm -rf /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY conf.d/ /usr/local/etc/php/conf.d/

# This stack targets rootless Docker: the host user is mapped to uid 0 inside the
# container, so running FPM as root is what keeps bind-mounted files owned by the
# host user. -R tells FPM that root is intentional. On rootful Docker you would
# instead set these to a user matching your host uid.
RUN sed -i 's/^user = .*/user = root/; s/^group = .*/group = root/' /usr/local/etc/php-fpm.d/www.conf \
 && git config --system --add safe.directory /app

ENV COMPOSER_HOME=/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /app
CMD ["php-fpm", "-R"]
