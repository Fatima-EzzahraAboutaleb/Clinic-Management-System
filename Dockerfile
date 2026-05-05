FROM php:8.1-apache

RUN sed -i 's/^#\(.*mpm_prefork\)/\1/' /etc/apache2/mods-enabled/*.conf 2>/dev/null || true

RUN cd /etc/apache2/mods-enabled && \
    rm -f mpm_event.conf mpm_event.load \
          mpm_worker.conf mpm_worker.load && \
    ln -sf ../mods-available/mpm_prefork.conf mpm_prefork.conf && \
    ln -sf ../mods-available/mpm_prefork.load mpm_prefork.load

WORKDIR /var/www/html/

COPY . .

EXPOSE 80