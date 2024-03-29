#!/bin/sh

# Exit script on error.
set -e

# Copy config files in to place after the container is running
# and the source code has been mounted to var/www/html.
# Do not overwrite existing files which the user may have customised.
for file in config email database; do
    if [ ! -f "application/config/$file.php" ]; then
      echo "Adding application/config/$file.php"
      cp "docker/warehouse/config/$file.php" application/config
      chown $UID:$GID "application/config/$file.php"
    fi
done

file="modules/rest_api/config/rest.php"
if [ ! -f "$file" ]; then
  echo "Adding modules/rest_api/config/rest.php"
  cp docker/warehouse/config/rest.php modules/rest_api/config
  sed -i -e "s|{{ User }}|${WAREHOUSE_API_USER}|" \
    -e "s|{{ Secret }}|${WAREHOUSE_API_SECRET}|" \
    -e "s|{{ Project Occ }}|${WAREHOUSE_API_PROJECT_OCC}|" \
    -e "s|{{ Project OccDel }}|${WAREHOUSE_API_PROJECT_OCC_DEL}|" \
    -e "s|{{ Project Smp }}|${WAREHOUSE_API_PROJECT_SMP}|" \
    -e "s|{{ Project SmpDel }}|${WAREHOUSE_API_PROJECT_SMP_DEL}|" \
    -e "s|{{ Elasticsearch address }}|${ELASTIC_PROXY_URL}|" \
    $file
  chown $UID:$GID $file
fi

# Ensure Elasticsearch CA certificate is registered
update-ca-certificates

# Start syslog
rsyslogd
# Start the cron daemon
service cron start

# Call the original entry point of the image to start apache.
docker-php-entrypoint apache2-foreground