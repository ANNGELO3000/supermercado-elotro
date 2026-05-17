FROM php:8.2-cli
RUN docker-php-ext-install mysqli
WORKDIR /app
COPY . /app

RUN echo "session.save_path = /tmp" >> /usr/local/etc/php/php.ini && \
    echo "session.gc_maxlifetime = 3600" >> /usr/local/etc/php/php.ini

EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80", "-t", "/app"]