# syntax=docker/dockerfile:1

# ============================================================
# Stage 1: Composer dependencies
# ============================================================
FROM composer:2.8 AS vendor

WORKDIR /app

RUN apk add --no-cache icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install intl zip mbstring bcmath pcntl

# Add build argument for composer dev dependencies

COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

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
    libsqlite3-dev \
    wget \
    bzip2 \
    ca-certificates \
    openbabel \
    supervisor \
    && docker-php-ext-install \
    pdo_sqlite \
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

# Accept Anaconda ToS (required in CI/Docker)
RUN conda tos accept --override-channels --channel https://repo.anaconda.com/pkgs/main && \
    conda tos accept --override-channels --channel https://repo.anaconda.com/pkgs/r

# Create environment
RUN conda create -y -p /var/www/html/vina_env -c conda-forge \
    vina=1.2.5 \
    python=3.10 \
    pip

# Install RDKit via pip to bypass Boost C++ solver compatibility conflicts
RUN /var/www/html/vina_env/bin/pip install rdkit==2025.09.5

# Install testing and service dependencies from requirements.txt
COPY requirements.txt /tmp/requirements.txt
RUN /var/www/html/vina_env/bin/pip install -r /tmp/requirements.txt

ENV PATH=/var/www/html/vina_env/bin:$PATH

WORKDIR /var/www/html

COPY --from=vendor --chown=www-data:www-data /app /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache database \
    && chown -R www-data:www-data storage bootstrap/cache database /var/www/html/vina_env

USER www-data

EXPOSE 8000 7860

HEALTHCHECK --interval=15s --timeout=5s --start-period=40s --retries=5 \
    CMD php -r "exit((int)(@file_get_contents('http://127.0.0.1:7860') === false));"

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
