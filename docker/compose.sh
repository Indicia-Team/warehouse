#!/bin/bash
# Exit script on error.
set -e

# Set the project name which determines the network and container names.
export COMPOSE_PROJECT_NAME=indicia

# Make .env variables available to scripts
source .env

# For additional debug information and to see the output of RUN
# commands in docker files modify the build command as follows:
#     BUILDKIT_PROGRESS=plain docker-compose build \
docker compose build --pull \
  --build-arg UID=$(id -u) \
  --build-arg GID=$(id -g) \
  --build-arg USER=$(id -un) \
  --build-arg GROUP=$(id -gn)

# We are using composer via the warehouse container to update
# vendor. This allows the PHP version to match the warehouse container,
# rather than host system.
docker compose run --rm --no-deps \
  --user 0:0 \
  --env COMPOSER_ALLOW_SUPERUSER=1 \
  --env XDEBUG_MODE=off \
  --workdir /var/www/html \
  --entrypoint composer \
  warehouse install --no-dev --no-interaction

# When the containers are brought up the database will initialise
# on first run.
# The warehouse executes its startup script which copies in some 
# config files then waits for the database to be ready. Next it 
# starts Apache and will respond to http requests.
# This is performed in the background.
docker compose up -d postgres warehouse elastic mailpit

# Run elasticsearch setup.
source elastic/setup.sh

# Run warehouse setup.
source warehouse/setup.sh

docker compose up -d logstash kibana geoserver pgadmin

# Clean up.
rm -f cookiefile
rm -f outputfile

echo
echo "You can visit the warehouse at http://localhost:8080"
echo "You can see email it sends at http://localhost:8025"
echo "You can examine the database at http://localhost:8070"
echo "You can manage GeoServer at http://localhost:8090/geoserver"
echo "You can access the ElasticSearch API at https://localhost:9200"
echo "You can use Kibana to view the indexes at https://localhost:5601"