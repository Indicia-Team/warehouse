# This image contains Debian's Apache httpd in conjunction with PHP7.3
# (as mod_php) and uses mpm_prefork by default.
# https://hub.docker.com/_/php
# Currently warehouse is not compatible with PHP 7.4
FROM php:7.3-apache
# Use PHP development configuration file
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
# Increase size of files which can be uploaded.
RUN sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 20M/' "$PHP_INI_DIR/php.ini"

# Add a tool to assist with installing PHP extensions.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions
# Install required extensions that are not already present in image.
RUN install-php-extensions gd pgsql zip xdebug
# Configure the xdebug extension for single-step
# debugging of code in the container.
#ENV XDEBUG_CONFIG=discover_client_host=1
ENV XDEBUG_CONFIG=client_host=172.17.0.1
ENV XDEBUG_MODE=debug

# Install a postgres client for the pg_isready command.
RUN apt-get update
RUN apt-get -y install postgresql-client
# Install cron for scheduled tasks
RUN apt-get -y install cron

# Create a user the same identity as the host user so that it can 
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
ENV USER=${USER}
ENV GROUP=${GROUP}

# Change Apache to listen on port 8080.
# We need it to appear on the same port both inside and outside the container
# as it is accessed from both perspectives.
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
RUN sed -i 's/<VirtualHost *:80>/<VirtualHost *:8080>/' /etc/apache2/sites-enabled/000-default.conf

# When a warehouse container is started, we need to execute a setup script.
# (The working directory is /var/www/html)
CMD docker/warehouse/startup.sh