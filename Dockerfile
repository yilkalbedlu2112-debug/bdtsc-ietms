FROM php:8.2-apache

# Required extensions
RUN apt-get update && apt-get install -y \
    libgd-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mysqli zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Fix MPM conflict — disable ALL first, then enable only prefork
RUN a2dismod mpm_event mpm_worker mpm_prefork 2>/dev/null || true \
    && a2enmod mpm_prefork

# Enable rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --ignore-platform-reqs --optimize-autoloader

# Apache config — AllowOverride All for .htaccess
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

EXPOSE 80