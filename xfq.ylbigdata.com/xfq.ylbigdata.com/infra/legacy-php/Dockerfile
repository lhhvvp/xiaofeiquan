FROM php:8.1-cli

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
      libfreetype6-dev \
      libicu-dev \
      libjpeg62-turbo-dev \
      libonig-dev \
      libpng-dev \
      libxml2-dev \
      libzip-dev \
    ; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
      bcmath \
      gd \
      intl \
      mbstring \
      mysqli \
      pdo_mysql \
      zip \
    ; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

CMD ["php", "think", "run", "--host", "0.0.0.0", "--port", "8000"]

