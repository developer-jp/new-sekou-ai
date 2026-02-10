# Use the official PHP 8.3 image.
# https://hub.docker.com/_/php
FROM php:8.3-apache

# Set the working directory inside the container based on the environment variable
WORKDIR /var/www

RUN set -xe; \
    apt-get update -yqq && \
    pecl channel-update pecl.php.net && \
    apt-get install -yqq \
      apt-utils \
      libzip-dev \
      libpng-dev \
      libjpeg62-turbo-dev \
      libfreetype6-dev \
      zip unzip vim git

# Use the instructions to install the latest node
RUN curl -sL https://deb.nodesource.com/setup_16.x | bash -
# Install nodejs
RUN apt-get install -y nodejs

# Install composer
RUN cd /usr/local/bin && \
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer && \
    php -r "unlink('composer-setup.php');"

# Install required PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo_mysql gd zip

# Copy local code to the container image.

COPY ./docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./docker/apache/php.ini "$PHP_INI_DIR/php.ini"

ENV PORT=80
ENV TZ Asia/Tokyo

# Apache DocumentRootを変更
ENV APACHE_DOCUMENT_ROOT /var/www/sekou-ai/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Composer install実行
# RUN cd /var/www/he-ai && /usr/local/bin/composer install

# RUN cd /var/www/he-ai && npm install && npm run build
# haruka-openAIフォルダのOwnerを変更
# RUN chown -R www-data:www-data /var/www/he-ai && chmod -R u+w /var/www/he-ai
# RUN chown -R www-data:www-data /work && chmod -R u+w /work
RUN chown -R www-data:www-data /var/www && chmod -R u+w /var/www
# Adjust user permission & group.
RUN usermod --uid 1000 www-data
RUN groupmod --gid 1000  www-data

# Apache のモジュールを有効化
RUN a2enmod rewrite && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

CMD ["apache2-foreground"]
