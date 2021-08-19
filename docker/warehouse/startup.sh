#!/bin/sh

# Copy config files in to place after the container is running
# and the source code has been mounted to var/www/html.
# Do not overwrite existing files which the user may have customised.
for file in config email database; do
    if [ ! -f "application/config/$file.php" ]; then
    cp "docker/warehouse/config/$file.php" application/config
    chown $UID:$GID "application/config/$file.php"
    fi
done

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