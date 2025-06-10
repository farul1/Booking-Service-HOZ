# Stage 1: Build the application with composer
FROM composer:2 as builder
WORKDIR /app
COPY . .
RUN composer install --no-interaction --no-plugins --no-scripts --prefer-dist

# Stage 2: Create the final production image
FROM php:8.2-fpm-alpine
WORKDIR /var/www

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Install ekstensi lain yang dibutuhkan
RUN docker-php-ext-install pdo pdo_mysql
# ============================================

# Copy composer dependencies
COPY --from=builder /app/vendor /var/www/vendor

# Copy application code
COPY . /var/www

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

CMD ["php-fpm"]
