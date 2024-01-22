#!/bin/sh

# Exit script on error.
set -e

# We can perform start up tasks as set out in 
# https://docs.docker.com/config/containers/multi-service_container

# Copy certificate files in to place after the container is running
# and the share volume has been mounted to
# /usr/share/elasticsearch/config/share.
cp config/certs/ca.crt config/share
cp config/certs/indicia-kibana-1/* config/share

# Run the original entrypoint script from
# https://github.com/elastic/elasticsearch/blob/eb1c490264870457d1c50228028a28d19f154f8e/distribution/docker/src/docker/Dockerfile
# I added the -s option as it seemed like a good thing.
tini -s -- /usr/local/bin/docker-entrypoint.sh eswrapper