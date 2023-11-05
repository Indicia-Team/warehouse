#!/bin/bash
# Exit script on error.
set -e

# Set the project name which determines the network and container names.
export COMPOSE_PROJECT_NAME=indicia

# Ensure dependencies of warehouse code have been installed on host.
if [ ! $(which composer) ]; then
  # Install composer locally if not present.
  echo "Installing composer..."
  ./getcomposer.sh
  COMPOSER_CMD='php composer.phar'
else
  # Use existing composer installation.
  COMPOSER_CMD='composer'
fi

$COMPOSER_CMD install \
  --working-dir=../ \
  --no-dev \
  --ignore-platform-req=ext-mbstring \
  --ignore-platform-req=ext-dom \
  --ignore-platform-req=ext-gd \
  --ignore-platform-req=ext-simplexml \
  --ignore-platform-req=ext-xml \
  --ignore-platform-req=ext-xmlreader \
  --ignore-platform-req=ext-xmlwriter \
  --ignore-platform-req=ext-zip


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