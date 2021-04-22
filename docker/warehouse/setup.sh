#!/bin/sh

# Copy config files in to place after the container is running
# and the source code has been mounted to var/www/html.
cp docker/warehouse/config/* application/config
# Docker runs as root so change file ownership back to the host user.
chown $UID:$GID application/config/config.php
chown $UID:$GID application/config/email.php
chown $UID:$GID application/config/database.php

# Wait till database is up before going any further.
echo "Waiting for Postgres."
until pg_isready -h postgres; do
    sleep 1
done

# Run apache with the same id as the host user.
export APACHE_RUN_USER=$USER
export APACHE_RUN_GROUP=$GROUP
# Call the original entry point of the image to start apache.
docker-php-entrypoint apache2-foreground