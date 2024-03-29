# This image contains Debian's Apache httpd in conjunction with PHP8.0.
# https://hub.docker.com/_/php
ARG PHP_VERSION
FROM php:${PHP_VERSION}-apache
# Use PHP development configuration file
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
# Add a tool to assist with installing PHP extensions.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions
# Install required extensions that are not already present in image.
RUN install-php-extensions gd pgsql zip xdebug pdo_pgsql
# Configure the xdebug extension for single-step
# debugging of code in the container.
#ENV XDEBUG_CONFIG=discover_client_host=1
ENV XDEBUG_CONFIG=client_host=172.17.0.1
ENV XDEBUG_MODE=debug

# Install operating system tools
RUN apt-get update \
    && apt-get -y install lsb-release wget gnupg

# Install postgres/postgis from the PostgreSQL apt repoitory.
ARG PG_VERSION
ENV PG_VERSION=${PG_VERSION}
RUN sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" > /etc/apt/sources.list.d/pgdg.list' \
    && wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add - \
    && apt-get update \
    && apt-get -y install postgresql-${PG_VERSION}-postgis-3 postgresql-contrib-${PG_VERSION}

# Enable postgresql query logging
RUN CONF=/etc/postgresql/${PG_VERSION}/main/postgresql.conf \
  && LOG=/var/lib/postgresql/${PG_VERSION}/main/log \
  && mkdir -p $LOG \
  && chown postgres:postgres $LOG \
  && sed -i "s/#logging_collector = off/logging_collector = on/" $CONF \
  && sed -i "s/#log_statement = 'none'/log_statement = 'all'/" $CONF 

# Enable postgresql to accept connection from pgAdminn
RUN CONF=/etc/postgresql/${PG_VERSION}/main/postgresql.conf \
  && sed -i "s/#listen_addresses = 'localhost'/listen_addresses = '*'/" $CONF \
  && CONF=/etc/postgresql/${PG_VERSION}/main/pg_hba.conf \
  && echo "host    all             all             0.0.0.0/0               md5" >> $CONF

# Create a user the same identity as the host user so that it can 
# share files on the bind mount containing the warehouse code which 
# docker-compose is going to set up.
ARG UID
ARG GID
ARG USER
ARG GROUP
RUN groupadd -g ${GID} ${GROUP}
RUN useradd -u ${UID} -g ${GID} ${USER}
RUN mkdir /home/$USER && chown $USER:$USER /home/$USER
# Store the args in the environment for use at run time.
ENV UID=${UID}
ENV GID=${GID}
ENV USER=${USER}
ENV GROUP=${GROUP}

ARG PORT
# Change Apache to listen on an available port.
# We need it to appear on the same port both inside and outside the container
# as it is accessed from both perspectives.
RUN sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
RUN sed -i "s/<VirtualHost *:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

# When a warehouse container is started, we need to execute a startup script.
# (The working directory is /var/www/html)
CMD docker/phpunit/startup.sh