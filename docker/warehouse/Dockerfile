# This image contains Debian's Apache httpd in conjunction with PHP8.1.
# https://hub.docker.com/_/php
FROM php:8.1-apache
# Use PHP development configuration file as a basis
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Add a tool to assist with installing PHP extensions.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions
# Install required extensions that are not already present in image.
RUN install-php-extensions gd pgsql zip xdebug

# Configure the xdebug extension for single-step debugging of code in the
# container.
ENV XDEBUG_MODE=debug
# Xdebug has to know the IP of the host where your dev environment is. It is
# thought a Docker host always has the IP of 172.17.0.1 on the docker network.
ENV XDEBUG_CONFIG=client_host=172.17.0.1
# Xdebug is set to trigger on all requests from browser, curl or whatever. This
# means you can debug the scripted setup phase. Comment out the following and
# rebuild if you prefer to trigger debugging with an XDEBUG_SESSION parameter or
# cookie, (see https://xdebug.org/docs/step_debug). Be aware that breakpoints
# will then only work on the first level. If the warehouse makes a second
# request to itself, as when it calls REST services to populate a table on a
# page, you'll need to insert a line of code containting "xdebug_break();" if
# you want to break at that second level. 
COPY config/xdebug.ini "$PHP_INI_DIR/conf.d/indicia-xdebug.ini"
# Override PHP limits in this file.
COPY config/php.ini "$PHP_INI_DIR/conf.d/indicia-php.ini"

# Install a postgres client for the pg_isready command.
RUN apt-get update
RUN apt-get -y install postgresql-client
# Install cron for scheduled tasks
RUN apt-get -y install cron
# Install rsyslog to collect cron logs
RUN apt-get -y install rsyslog
# Configure cron to log more
RUN echo 'EXTRA_OPTS="-L 15"' >> /etc/default/cron

# Create a user with the same identity as the host user so that it can 
# share files on the bind mount containing the warehouse code which 
# docker-compose is going to set up.
ARG UID
ARG GID
ARG USER
ARG GROUP
RUN groupadd -g ${GID} ${GROUP}
RUN useradd -u ${UID} -g ${GID} ${USER}
# Store the args in the environment for use at run time.
ENV UID=${UID}
ENV GID=${GID}
# Run apache with the same id as the host user.
ENV APACHE_RUN_USER=${USER}
ENV APACHE_RUN_GROUP=${GROUP}

# Change Apache to listen on port 8080.
# We need it to appear on the same port both inside and outside the container
# as it is accessed from both perspectives.
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost *:80>/<VirtualHost *:8080>/' /etc/apache2/sites-enabled/000-default.conf

# When a warehouse container is started, we need to execute a startup script.
# (The working directory is /var/www/html)
ENTRYPOINT [ "/bin/sh", "docker/warehouse/entrypoint.sh"]