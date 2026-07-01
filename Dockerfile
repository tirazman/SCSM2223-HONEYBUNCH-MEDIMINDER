FROM php:8.2-cli

WORKDIR /var/www

# Install system dependencies first
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    curl \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]