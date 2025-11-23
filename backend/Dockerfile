FROM php:8.2-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    git \
    unzip \
    libpq-dev \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY . /var/www

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN if [ -f composer.json ]; then \
      composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist; \
    else \
      echo "No composer.json found — skipping composer install"; \
    fi

RUN sed -i 's/listen = .*/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
 && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
