ARG XDEBUG_VERSION="xdebug"
#idk why i need this

FROM php:8.2-fpm-alpine

RUN apk update
#RUN apt-get install -y libmagickwand-dev imagemagick; \
#    pecl install imagick; \
#    docker-php-ext-enable imagick;
RUN apk add --no-cache ${PHPIZE_DEPS} imagemagick imagemagick-dev libgomp
RUN pecl install -o -f imagick \
    &&  docker-php-ext-enable imagick

# Install xdebug according to the Docker image you're using
# RUN pecl install xdebug && 

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

# Add xdebug
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS
RUN apk add --update linux-headers
RUN pecl install xdebug-3.1.5
RUN docker-php-ext-enable xdebug
RUN apk del -f .build-deps

# Configure Xdebug
RUN echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.log=/var/www/html/xdebug/xdebug.log" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.discover_client_host=1" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.client_port=5902" >> /usr/local/etc/php/conf.d/xdebug.ini

# Copy php.ini-production
COPY prod.ini "$PHP_INI_DIR/php.ini-production"
COPY pool.ini "/usr/local/etc/php-fpm.conf"
COPY www.ini "/usr/local/etc/php-fpm.d/www.conf"
COPY www.ini "/usr/local/etc/php-fpm.d/www.default.conf"
# Then move into php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"