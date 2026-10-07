# ---------- Stage 1: ติดตั้ง PHP dependencies ด้วย Composer ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# ---------- Stage 2: PHP + Apache ----------
FROM php:8.3-apache

# PostgreSQL extensions + mod_headers (ใช้ใน .htaccess)
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev postgresql-client \
    && docker-php-ext-install pgsql pdo_pgsql \
    && a2enmod headers rewrite \
    && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && rm -rf /var/lib/apt/lists/*

ENV TZ=Asia/Bangkok
RUN echo "date.timezone=Asia/Bangkok" > /usr/local/etc/php/conf.d/timezone.ini

# โปรเจกต์อ้างอิง path /PID/ (เหมือนตอนรันบน XAMPP) จึงวางไว้ที่ /var/www/html/PID
WORKDIR /var/www/html/PID
COPY . .
COPY --from=vendor /app/vendor ./vendor

# เข้า http://host/ แล้วพาไป /PID/ อัตโนมัติ
RUN echo '<?php header("Location: /PID/"); exit;' > /var/www/html/index.php \
    && chown -R www-data:www-data /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
# ตัด CR ออก เผื่อไฟล์ถูก checkout บน Windows เป็น CRLF
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
