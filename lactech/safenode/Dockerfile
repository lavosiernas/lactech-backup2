# SafeNode - Dockerfile
# Imagem Docker otimizada para produção

FROM php:8.1-fpm-alpine

# Instalar dependências
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    curl \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql opcache

# Instalar Redis extension (opcional)
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del pcre-dev $PHPIZE_DEPS

# Configurar PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/safenode.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Configurar Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/safenode.conf /etc/nginx/conf.d/default.conf

# Configurar Supervisor
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copiar código da aplicação
WORKDIR /var/www/html
COPY . .

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/logs /var/www/html/backups \
    && chown -R www-data:www-data /var/www/html/logs /var/www/html/backups

# Expor porta
EXPOSE 80

# Iniciar Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]



