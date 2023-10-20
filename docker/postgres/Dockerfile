# Get the postgres 13 server with postgis 3.1 installed.
# See https://registry.hub.docker.com/r/postgis/postgis/tags
# for other versions available
FROM postgis/postgis:13-3.3
# Copy the database setup script to a location where it will be
# automatically found and executed.
COPY ./setup.sh /docker-entrypoint-initdb.d