FROM php:7.1-cli
RUN apt-get update && \
    apt-get install -y libssl-dev && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb && \
    apt-get install -y mongodb && \
    apt-get update && \
    apt-get install -y curl && \
    service mongodb start && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . /usr/src/mongodb-client
WORKDIR /usr/src/mongodb-client

RUN composer install --no-interaction

CMD [ "php", "./public/index.php" ]