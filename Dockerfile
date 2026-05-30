# syntax=docker/dockerfile:1

# ============================================================
# Stage 1: Composer dependencies
# ============================================================
FROM composer:2.8 AS vendor

WORKDIR /app

RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install intl zip mbstring bcmath pcntl

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

# ============================================================
# Stage 2: Frontend assets (Vite)
# ============================================================
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# ============================================================
# Stage 3: Runtime (PHP CLI on Debian — artisan serve)
# ============================================================
FROM php:8.3-cli AS runtime

RUN apt-get update && apt-get install -y \
    libicu-dev \
    libzip-dev \
    libonig-dev \
    wget \
    bzip2 \
    ca-certificates \
    && docker-php-ext-install \
    pdo_mysql \
    zip \
    intl \
    mbstring \
    bcmath \
    opcache \
    pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Install Miniconda
ENV CONDA_DIR=/opt/conda
RUN wget --quiet https://repo.anaconda.com/miniconda/Miniconda3-latest-Linux-x86_64.sh -O /tmp/miniconda.sh && \
    /bin/bash /tmp/miniconda.sh -b -p $CONDA_DIR && \
    rm /tmp/miniconda.sh

ENV PATH=$CONDA_DIR/bin:$PATH

# Create Conda Environment for Vina, RDKit, Open Babel
RUN conda create -y -p /var/www/html/vina_env -c conda-forge \
    rdkit=2025.09.5 \
    vina=1.2.5 \
    openbabel=3.1.0 \
    python=3.10

ENV PATH=/var/www/html/vina_env/bin:$PATH

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/laravel.env .env
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache .env /var/www/html/vina_env

USER www-data

EXPOSE 8000

HEALTHCHECK --interval=15s --timeout=5s --start-period=40s --retries=5 \
    CMD php -r "exit((int)(@file_get_contents('http://127.0.0.1:8000') === false));"

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
