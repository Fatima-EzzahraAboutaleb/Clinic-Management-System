FROM php:8.1-apache

# Explicitly fix MPM conflict
RUN a2dismod mpm_event || true && \
    a2dismod mpm_worker || true && \
    a2enmod mpm_prefork

WORKDIR /var/www/html/

COPY . .

EXPOSE 80