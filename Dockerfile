FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libgd-dev libzip-dev libpng-dev \
    libjpeg-dev libfreetype6-dev \
    libonig-dev \
    zip unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql mysqli zip mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --ignore-platform-reqs --optimize-autoloader

EXPOSE ${PORT:-8080}

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t ."]