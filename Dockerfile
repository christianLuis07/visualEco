# ============================================================
#  Visueco - Image untuk service "visueco-app"
#  Base : PHP 8.2-FPM (Laravel + MySQL ready)
# ============================================================
FROM php:8.2-fpm

# --- Metadata ---
LABEL maintainer="Visueco DevOps Team"
LABEL description="PHP 8.2-FPM image for Visueco (AI Waste Audit - SDGs 12)"

# --- Argumen UID/GID (default 1000) agar fleksibel lintas OS ---
ARG UID=1000
ARG GID=1000

# ------------------------------------------------------------
# 1. Instalasi dependensi sistem
#    Digabung dalam satu layer + pembersihan cache apt
#    untuk menjaga ukuran image tetap ramping.
# ------------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        curl \
        libpng-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libxml2-dev \
        zip \
        unzip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ------------------------------------------------------------
# 2. Instalasi ekstensi PHP internal
#    Krusial untuk Laravel & koneksi MySQL.
#    GD dikonfigurasi dengan dukungan JPEG untuk image processing.
# ------------------------------------------------------------
RUN docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd

# ------------------------------------------------------------
# 3. Ambil binary Composer terbaru dari official image
# ------------------------------------------------------------
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ------------------------------------------------------------
# 4. Direktori kerja aplikasi
# ------------------------------------------------------------
WORKDIR /var/www

# ------------------------------------------------------------
# 5. User non-root "www" (UID:GID 1000)
#    Mencegah permission issue pada bind mount di Linux/macOS.
# ------------------------------------------------------------
RUN groupadd -g ${GID} www \
    && useradd -u ${UID} -g www -m -s /bin/bash www \
    && chown -R www:www /var/www

# Jalankan container sebagai user non-root
USER www

# PHP-FPM mendengarkan pada port 9000
EXPOSE 9000

CMD ["php-fpm"]
