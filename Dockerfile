FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo_mysql gd
RUN echo 'upload_max_filesize=20M' > /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'post_max_size=25M' >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'max_file_uploads=10' >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo 'memory_limit=256M' >> /usr/local/etc/php/conf.d/uploads.ini

COPY . /var/www/html/

RUN mkdir -p /var/www/html/uploads/dynamics && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80