FROM php:8.1-apache

# Nuclear option - remove ALL mpm modules and reinstall only prefork
RUN apt-get update && \
    apt-get remove -y apache2 && \
    apt-get install -y apache2 && \
    a2dismod mpm_event mpm_worker mpm_prefork || true && \
    a2enmod mpm_prefork && \
    echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html/

COPY . .

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]