FROM php:8.2-cli
RUN docker-php-ext-install mysqli
WORKDIR /app
COPY . /app
EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80"]