FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    build-base \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libfreetype-dev \
    postgresql-dev \
    git \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install \
    bcmath \
    ctype \
    fileinfo \
    json \
    mbstring \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    tokenizer \
    xml

# Install GD with JPEG and FreeType support
RUN docker-php-ext-configure gd \
    --with-freetype=/usr/include/ \
    --with-jpeg=/usr/include/ && \
    docker-php-ext-install gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create non-root user
RUN addgroup -g 1000 laravel && \
    adduser -D -u 1000 -G laravel laravel

# Set working directory
WORKDIR /app

# Copy application files
COPY --chown=laravel:laravel . .

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev

# Set up proper permissions
RUN chown -R laravel:laravel /app && \
    chmod -R 755 /app/storage && \
    chmod -R 755 /app/bootstrap/cache

USER laravel

EXPOSE 9000

CMD ["php-fpm"]
