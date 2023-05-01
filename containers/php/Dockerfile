FROM php:fpm-alpine3.17

RUN apk update
#RUN apt-get install -y libmagickwand-dev imagemagick; \
#    pecl install imagick; \
#    docker-php-ext-enable imagick;
RUN apk add --no-cache ${PHPIZE_DEPS} imagemagick imagemagick-dev libgomp
RUN pecl install -o -f imagick \
    &&  docker-php-ext-enable imagick
RUN apk del --no-cache ${PHPIZE_DEPS}
RUN apk add icu-dev zlib zlib-dev libpng-dev
RUN docker-php-ext-install intl
RUN docker-php-ext-install opcache
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-configure exif
RUN docker-php-ext-install exif
RUN docker-php-ext-enable exif
RUN docker-php-ext-install gd

# Copy php.ini-production
COPY prod.ini "$PHP_INI_DIR/php.ini-production"
COPY pool.ini "/usr/local/etc/php-fpm.conf"
COPY www.ini "/usr/local/etc/php-fpm.d/www.conf"
COPY www.ini "/usr/local/etc/php-fpm.d/www.default.conf"
# Then move into php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"