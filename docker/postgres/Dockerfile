# Get the postgres 17 server with postgis 3.5 installed.
# See https://registry.hub.docker.com/r/postgis/postgis/tags
# for other versions available
FROM postgis/postgis:17-3.5-alpine
# Copy the database setup script to a location where it will be
# automatically found and executed.
# Only runs if the data directory is empty.
COPY ./setup.sh /docker-entrypoint-initdb.d