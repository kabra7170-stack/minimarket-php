FROM php:8.2-cli

WORKDIR /app
COPY . /app

RUN find /app -name "index.html" -delete

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "/app", "login.php"]