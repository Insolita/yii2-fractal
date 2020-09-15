FROM yiisoftware/yii2-php:7.1-apache

ENV INSIDE_DOCKER=1

COPY apache.conf /etc/apache2/sites-enabled/000-default.conf

# enable xdebug
RUN docker-php-ext-enable xdebug
COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini