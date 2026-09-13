# syntax=docker/dockerfile:1

FROM php:8.1-cli AS composer-builder

WORKDIR /var/www/html

# Install the PHP extensions required for dependency resolution and the application runtime.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        git \
        libcurl4-openssl-dev \
        libonig-dev \
        libssl-dev \
        libzip-dev \
        unzip \
        zlib1g-dev \
    && docker-php-ext-install curl mbstring exif fileinfo pdo_mysql zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

# Copy dependency metadata first so Docker can cache the dependency layer efficiently.
COPY composer.json composer.lock ./

# Install the project dependency set, including dev dependencies required by local Laravel bootstrapping.
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader

# Copy the application source and generate the optimized autoloader.
COPY . .
RUN composer dump-autoload --optimize

FROM php:8.1-cli AS runtime

WORKDIR /var/www/html

# Install only the runtime PHP extensions required by the app.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        libcurl4-openssl-dev \
        libonig-dev \
        libssl-dev \
        libzip-dev \
        unzip \
        zlib1g-dev \
    && docker-php-ext-install curl mbstring exif fileinfo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd --system --gid 1000 laravel \
    && useradd --system --uid 1000 --gid laravel --create-home --home-dir /home/laravel laravel

# Copy only the built application artifacts from the builder stage.
COPY --from=composer-builder --chown=laravel:laravel /var/www/html /var/www/html

# Ensure writable directories are owned by the non-root user.
RUN chown -R laravel:laravel /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache

# Run the app as a non-root user.
USER laravel

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
