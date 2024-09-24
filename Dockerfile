FROM php:7.2-apache
RUN docker-php-ext-install bcmath
COPY src/ /var/www/html/
RUN chmod 0777 /var
RUN chmod 0777 /var/www/html/conf
