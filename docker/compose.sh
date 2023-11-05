#!/bin/bash

# Set the project name which determines the network and container names.
export COMPOSE_PROJECT_NAME=indicia

# Ensure dependencies of warehouse code have been installed on host.
if [ ! $(which composer) ]; then
  # First install composer if not present
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
  && php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
  && php composer-setup.php \
  && php -r "unlink('composer-setup.php');" \
  && mv composer.phar /usr/local/bin/composer
fi
composer --working-dir=../ install --no-dev

# For additional debug information and to see the output of RUN
# commands in docker files modify the build command as follows:
#     BUILDKIT_PROGRESS=plain docker-compose build \
docker compose build --pull \
  --build-arg UID=$(id -u) \
  --build-arg GID=$(id -g) \
  --build-arg USER=$(id -un) \
  --build-arg GROUP=$(id -gn)
# When the containers are brought up the database will initialise
# on first run.
# The warehouse executes its startup script which copies in some 
# config files then waits for the database to be ready. Next it 
# starts Apache and will respond to http requests.
# This is performed in the background.
docker compose up -d

# Run warehouse setup.
source warehouse/setup.sh

# Run elasticsearch setup.
source elastic/setup.sh

# Clean up.
rm -f cookiefile
rm -f outputfile

echo
echo "You can visit the warehouse at http://localhost:8080"
echo "You can see email it sends at http://localhost:8025"
echo "You can examine the database at http://localhost:8070"
echo "You can manage GeoServer at http://localhost:8090/geoserver"
echo "You can access the ElasticSearch API at http://localhost:9200"
echo "You can use Kibana to view the indexes at http://localhost:5601"