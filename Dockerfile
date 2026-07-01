FROM php:8.2-cli

WORKDIR /var/www

RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip curl git \
    && rm -rf /var/lib/apt/lists/*

# Install PDO MySQL extension ← add this
RUN docker-php-ext-install pdo pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY server/ .

RUN composer install --no-dev --optimize-autoloader

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]