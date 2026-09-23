FROM dunglas/frankenphp:latest-php8.3

# Dependențe de sistem necesare pentru extensiile PHP de mai jos
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    pkg-config \
    zip \
    unzip \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Extensii PHP standard
# Extensii PHP standard
RUN install-php-extensions \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    mysqli \
    mbstring \
    exif \
    bcmath \
    gd \
    redis \
    opcache \
    zip \
    intl
    
# Extensia MongoDB (nu e inclusă în install-php-extensions implicit pe toate versiunile,
# se instalează separat prin PECL pentru siguranță)
RUN pecl install mongodb-2.3.0 && docker-php-ext-enable mongodb

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN chmod +x /usr/local/bin/frankenphp

WORKDIR /var/www/html
COPY . /var/www/html

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=3s --start-period=15s \
  CMD curl -f http://localhost:8000/ || exit 1
