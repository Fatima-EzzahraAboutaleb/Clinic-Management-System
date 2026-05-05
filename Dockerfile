FROM ubuntu:22.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && \
    apt-get install -y apache2 php8.1 php8.1-mysql libapache2-mod-php8.1 && \
    apt-get clean

# Disable ALL MPMs then enable only prefork
RUN a2dismod mpm_event mpm_worker mpm_prefork 2>/dev/null || true && \
    a2enmod mpm_prefork && \
    a2enmod php8.1

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /var/www/html/
RUN rm -f index.html

COPY . .

RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80

CMD ["apache2ctl", "-D", "FOREGROUND"]