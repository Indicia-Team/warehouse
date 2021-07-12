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

# Install composer.
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer

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

# Install phpunit globally for use by host user.
RUN runuser -l $USER -c 'composer global require "phpunit/phpunit"'
RUN runuser -l $USER -c 'composer global require "misantron/dbunit:^5.1"'
RUN runuser -l $USER -c "composer global config home"
ENV PATH="${PATH}:/home/$USER/.composer/vendor/bin"

ARG PORT
# Change Apache to listen on an available port.
# We need it to appear on the same port both inside and outside the container
# as it is accessed from both perspectives.
RUN sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
RUN sed -i "s/<VirtualHost *:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf

# When a warehouse container is started, we need to execute a startup script.
# (The working directory is /var/www/html)
CMD docker/phpunit/startup.sh