FROM openswoole/swoole:php8.2

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN apt-get update && apt-get install -y \ 
    libz-dev libpq-dev libmagickwand-dev zip git unzip wget libc-ares-dev libcurl4-openssl-dev build-essential inotify-tools

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions mbstring 

RUN install-php-extensions intl

RUN install-php-extensions curl

RUN install-php-extensions redis

RUN install-php-extensions zip

RUN install-php-extensions opcache

RUN install-php-extensions pdo

RUN install-php-extensions pgsql

RUN install-php-extensions pdo_pgsql

RUN install-php-extensions phalcon

RUN install-php-extensions inotify

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \ 
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer 

ENTRYPOINT ["/main/docker/start.sh"]

EXPOSE 9500