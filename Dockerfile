FROM ubuntu:22.04
ENV DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y apache2 php8.1 php8.1-mysql libapache2-mod-php8.1
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN rm -rf /var/www/html/*
COPY . /var/www/html/
RUN echo '<meta http-equiv="refresh" content="0;url=/login.php">' > /var/www/html/index.html
RUN chown -R www-data:www-data /var/www/html
CMD ["apache2ctl", "-D", "FOREGROUND"]
EXPOSE 80