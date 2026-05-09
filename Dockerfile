FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y apache2 php8.1 php8.1-mysql libapache2-mod-php8.1
COPY . /var/www/html/
RUN rm /var/www/html/index.html 2>/dev/null; chown -R www-data:www-data /var/www/html
ENV PORT=80
CMD ["apache2ctl", "-D", "FOREGROUND"]
EXPOSE 80