FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && \
    apt-get install -y apache2 php8.1 php8.1-mysql libapache2-mod-php8.1 && \
    apt-get clean

RUN a2dismod mpm_event mpm_worker mpm_prefork 2>/dev/null || true && \
    a2enmod mpm_prefork && \
    a2enmod php8.1

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN rm -f /var/www/html/index.html

RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    DirectoryIndex login.php index.php index.html\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-enabled/000-default.conf

WORKDIR /var/www/html/
COPY . .

RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]