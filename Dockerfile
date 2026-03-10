# Dockerfile para Laravel com PHP 8.4 FPM + Node.js 20
FROM php:8.4-fpm

# Arguments
ARG USER_ID=1000
ARG GROUP_ID=1000

# Instalar dependências do sistema (otimizado para economia de espaço)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libicu-dev \
    gnupg \
    ca-certificates \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Instalar PHP Extension Installer (mlocati)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# Instalar extensões PHP obrigatórias usando mlocati
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    xml \
    ctype \
    json \
    openssl \
    curl \
    dom \
    fileinfo \
    filter \
    hash \
    session \
    tokenizer \
    pcntl \
    zip \
    gd \
    bcmath \
    redis

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js 20 LTS e NPM
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest \
    && rm -rf /var/lib/apt/lists/*

# Criar usuário laravel com UID/GID 1000
RUN groupadd -g ${GROUP_ID} laravel \
    && useradd -u ${USER_ID} -g laravel -m -d /var/www -s /bin/bash laravel

# Definir diretório de trabalho
WORKDIR /var/www

# Copiar código da aplicação
COPY --chown=laravel:laravel . /var/www

# Garantir permissões corretas em storage e bootstrap/cache
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R laravel:laravel /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Configurar PHP-FPM para rodar como usuário laravel
RUN sed -i 's/user = www-data/user = laravel/g' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/group = www-data/group = laravel/g' /usr/local/etc/php-fpm.d/www.conf

# Expor porta do PHP-FPM
EXPOSE 9000

# Expor porta do Vite para HMR
EXPOSE 5173

# Mudar para o usuário laravel
USER laravel

# Comando padrão
CMD ["php-fpm"]
