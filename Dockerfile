FROM php:8.3.4-fpm-alpine as base

# Install dependencies
RUN apk add --no-cache postgresql-dev msmtp perl wget procps shadow libzip libpng libjpeg-turbo libwebp freetype icu nginx supervisor

# Install PHP extensions
RUN apk add --no-cache --virtual build-essentials \
    icu-dev icu-libs zlib-dev g++ make automake autoconf libzip-dev \
    libpng-dev libwebp-dev libjpeg-turbo-dev freetype-dev && \
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install gd && \
    docker-php-ext-install pgsql && \
    docker-php-ext-install pgsql pdo pdo_pgsql && \
    docker-php-ext-install intl && \
    docker-php-ext-install bcmath && \
    docker-php-ext-install opcache && \
    docker-php-ext-install exif && \
    docker-php-ext-install zip && \
    apk del build-essentials && rm -rf /usr/backend/php*

# Install ImageMagick
RUN apk add --update --no-cache autoconf g++ imagemagick imagemagick-dev libtool make pcre-dev \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && apk del autoconf g++ libtool make pcre-dev

ENV COMPOSER_HOME ./.composer
COPY --from=composer:2.7.7 /usr/bin/composer /usr/bin/composer

FROM base AS deps

COPY backend/composer.json /var/www/html/composer.json
COPY backend/composer.lock /var/www/html/composer.lock

RUN composer install --no-dev --no-autoloader --no-scripts

FROM base AS prod

# Create nginx and supervisor directories
RUN mkdir -p /run/nginx /var/log/supervisor

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

COPY --chown=www-data:www-data backend/ /var/www/html
COPY --chown=www-data:www-data --from=deps /var/www/html/vendor /var/www/html/vendor

RUN composer dump-autoload --optimize
RUN php artisan optimize

# Set working directory
WORKDIR /var/www/html

# Start supervisord
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

# Expose port 80
EXPOSE 80