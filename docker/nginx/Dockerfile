FROM nginx:1.28-alpine AS main

## Configure sshd for Azure App Service (optional)
RUN apk add --no-cache openssh openssh-client openssl apache2-utils \
    && ssh-keygen -A \
    && mkdir -p /run/sshd
COPY ./docker/sshd_config.azure_app_service /etc/ssh/sshd_config
COPY ./docker/nginx/entrypoint.sh /entrypoint.sh
RUN chmod 0755 /entrypoint.sh


# Copy static files to be served by nginx
COPY --parents public/ /var/www/html/


# Webserver configuration
COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf
RUN nginx -t


## Expose:
## - 80: http
## - 2223: custom sshd port (Azure often maps it)
EXPOSE 80 2222
ENTRYPOINT ["/entrypoint.sh"]
