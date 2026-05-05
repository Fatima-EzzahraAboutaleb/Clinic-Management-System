FROM php:8.1-apache

# Fix: disable conflicting MPMs, enable only prefork
RUN a2dismod mpm_event mpm_worker 2>/dev/null; \
    a2enmod mpm_prefork rewrite

WORKDIR /var/www/html/

COPY . .

EXPOSE 80