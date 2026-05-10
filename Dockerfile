FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN a2dismod mpm_event || true && a2enmod mpm_prefork || true
RUN rm -f /var/www/html/index.html
COPY . /var/www/html/
RUN echo '<meta http-equiv="refresh" content="0;url=/login.php">' > /var/www/html/index.html
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]