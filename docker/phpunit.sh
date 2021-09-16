#!/bin/bash

# Set the port on which the warehouse will be accessible
# to the host.
export PORT=8080
# Set the project name which determines the network and container names.
export COMPOSE_PROJECT_NAME=phpunit

# Ensure dependencies of warehouse have been installed
if [ ! $(which composer) ]; then
  # First install composer if not present
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
  && php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
  && php composer-setup.php \
  && php -r "unlink('composer-setup.php');" \
  && mv composer.phar /usr/local/bin/composer
fi
composer --working-dir=../ install

# The phpunit container is built to replicate the CI environment
# allowing us to run tests locally before pushing commits.
# The container is built with webserver and database together.
docker-compose -f docker-compose-phpunit.yml build \
  --build-arg UID=$(id -u) \
  --build-arg GID=$(id -g) \
  --build-arg USER=$(id -un) \
  --build-arg GROUP=$(id -gn) \
  --build-arg PHP_VERSION=8 \
  --build-arg PG_VERSION=13 \
  --build-arg PORT=$PORT
# When the container is brought up, the database will start 
# followed by Apache which will respond to http requests.
# This is performed in the background.
docker-compose -f docker-compose-phpunit.yml up -d

# Wait for warehouse to be up
echo "Waiting for warehouse..."
until curl --silent --output outputfile http://localhost:${PORT}; do
  sleep 1
done
echo "Warehouse is up."

# Backup any existing config files on the host
BACKUP=( \
  ../application/config/config.php \
  ../application/config/indicia.php \
  ../application/config/database.php \
  ../application/config/email.php \
  ../modules/rest_api/config/rest.php \
  ../modules/spatial_index_builder/config/spatial_index_builder.php \
  ../modules/request_logging/config/request_logging.php
)
for FILE in ${BACKUP[@]}; do
  if [ -f $FILE ]; then
    mv -f $FILE ${FILE}.phpunit-backup
  fi
done;

# Enable the phpunit module in config.php (meaning initialise() is not tested)
DIR=../application/config
cp ${DIR}/config.php.travis ${DIR}/config.php
# Alter site domain as apache is on a different port compared to Travis
sed -i "s/127.0.0.1/127.0.0.1:${PORT}/" ${DIR}/config.php
# Provide a config file for the rest_api, spatial_index_builder and request_logging modules
DIR=../modules/rest_api/config
cp ${DIR}/rest.php.travis  ${DIR}/rest.php
DIR=../modules/spatial_index_builder/config
cp ${DIR}/spatial_index_builder.php.travis  ${DIR}/spatial_index_builder.php
DIR=../modules/request_logging/config
cp ${DIR}/request_logging.example.php ${DIR}/request_logging.php

# Run the tests in the container as the host user.
# (This ensures that e.g. log files created by phpunit are equally
# accessible to the Apache process and the host user too.)
# The XDEBUG_CONFIG is to allow breakpoints to be triggered as tests run.
# 172.17.0.1 is the IP address of the Docker host seen from a container.
# The idekey is for a suitably configured Visual Studio Code debugging client.
docker exec -t -e XDEBUG_CONFIG="idekey=VSCODE client_host=172.17.0.1" phpunit_warehouse_1 sh -c '
 runuser -u $USER -- pwd && \
    vendor/bin/phpunit --stderr --configuration phpunit-config-test.xml && \
    vendor/bin/phpunit --stderr --configuration phpunit-setup-check-test.xml && \
    vendor/bin/phpunit --stderr --configuration phpunit-home-test.xml && \
    # Repeat to upgrade modules
    vendor/bin/phpunit --stderr --configuration phpunit-home-test.xml
'

# Now the Indicia schema exists we can assign permissions to the 
# indicia_report_user.
docker exec -t phpunit_warehouse_1 sh -c '
  runuser -u postgres -- psql indicia -c "
  GRANT USAGE ON SCHEMA indicia TO indicia_report_user;
  ALTER DEFAULT PRIVILEGES IN SCHEMA indicia GRANT SELECT ON TABLES TO indicia_report_user;
  GRANT SELECT ON ALL TABLES IN SCHEMA indicia TO indicia_report_user;
  "
'

docker exec -t -e XDEBUG_CONFIG="idekey=VSCODE client_host=172.17.0.1" phpunit_warehouse_1 sh -c '
  runuser -u $USER -- pwd && \
  vendor/bin/phpunit --stderr --configuration phpunit-tests.xml
'

# Allow user a chance to modify code and rerun application/module tests.
while true; do
  prompt="Would you like to re-run application and module tests (Y/n)?"
  read -rs -n 1 -p "$prompt" 
  echo
  if [ "$REPLY" = "N" ] || [ "$REPLY" = "n" ]; then
    break
  fi
  docker exec -t -e XDEBUG_CONFIG="idekey=VSCODE client_host=172.17.0.1" phpunit_warehouse_1 sh -c '
    runuser -u $USER -- vendor/bin/phpunit --stderr --configuration phpunit-tests.xml
  '
done

# Restore backed-up files.
for FILE in ${BACKUP[@]}; do
  if [ -f ${FILE}.phpunit-backup ]; then
    mv -f ${FILE}.phpunit-backup $FILE
  fi
done;

# Clean up.
rm -f cookiefile
rm -f outputfile

docker-compose -f docker-compose-phpunit.yml down
