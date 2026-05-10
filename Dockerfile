FROM php:8.2-fpm-alpine
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apk add --no-cache nginx
RUN rm -f /var/www/html/index.html
COPY . /var/www/html/
RUN echo '<meta http-equiv="refresh" content="0;url=/login.php">' > /var/www/html/index.html
RUN chown -R www-data:www-data /var/www/html
COPY <<EOF /etc/nginx/http.d/default.conf
server {
    listen \${PORT:-80};
    root /var/www/html;
    index login.php index.php;
    location / {
        try_files \$uri \$uri/ /login.php;
    }
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF
EXPOSE 80
CMD sh -c "php-fpm -D && nginx -g 'daemon off;'"