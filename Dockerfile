FROM php:8.2-apache
RUN docker-php-ext-install mysqli
RUN a2enmod rewrite session
COPY . /var/www/html/
EXPOSE 80