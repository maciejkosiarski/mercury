ARG BASE_IMAGE
FROM ${BASE_IMAGE}

RUN apt-get update && \
    apt-get install -y libfreetype6-dev \
    libjpeg62-turbo-dev libpng-dev \
    zlib1g-dev libzip-dev \
    libcurl4-openssl-dev pkg-config libssl-dev \
    libpng-dev libxml2-dev libicu-dev g++ \
    libpq-dev unzip librabbitmq-dev \
    libyaml-dev

RUN docker-php-ext-install -j$(nproc) iconv \
    && docker-php-ext-configure gd \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo soap sockets bcmath zip \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install intl pdo_pgsql pdo_mysql pcntl \
    && rm -rf /var/lib/apt/lists/* \
#    && pecl clear-cache \
    && pecl config-set php_ini /usr/local/etc/php/conf.d/over.ini
