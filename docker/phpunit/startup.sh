#!/bin/sh

# Ensure Composer packages are installed and add PHPUnit to path
# as host user. (Includes PHPUnnit and DBUnit.)
runuser -u $USER -- composer install

# Start postgresql as user postgres
runuser -u postgres -- pg_ctlcluster $PG_VERSION main start
# Wait till database is up before going any further.
echo "Waiting for Postgres."
until pg_isready; do
    sleep 1
done

# Create indicia database
runuser -u postgres -- psql -d postgres -c 'CREATE DATABASE indicia;'
# Configure indicia database
runuser -u postgres -- psql -d indicia <<EOF
-- Add extension for PostGIS
CREATE EXTENSION postgis;
CREATE EXTENSION postgis_topology;
-- Add extension for btree_gin indexes.
CREATE EXTENSION btree_gin;

-- Create indicia_user and allocate permissions.
CREATE USER indicia_user WITH PASSWORD 'indicia_user_pass';
GRANT ALL PRIVILEGES ON DATABASE indicia TO indicia_user;
GRANT ALL PRIVILEGES ON TABLE geometry_columns TO indicia_user;
GRANT ALL PRIVILEGES ON TABLE spatial_ref_sys TO indicia_user;
GRANT EXECUTE ON FUNCTION st_astext(geometry) TO indicia_user;
GRANT EXECUTE ON FUNCTION st_geomfromtext(text, integer) TO indicia_user;
GRANT EXECUTE ON FUNCTION st_transform(geometry, integer) TO indicia_user;
ALTER USER indicia_user SET search_path = indicia, public, pg_catalog;

-- Create indicia_report_user and allocate permissions.
CREATE USER indicia_report_user WITH PASSWORD 'indicia_report_user_pass';
ALTER USER indicia_report_user SET search_path = indicia, public, pg_catalog;

-- Ensure web mercator projection is correct.
DELETE FROM spatial_ref_sys WHERE srid=900913;
INSERT into spatial_ref_sys (srid, auth_name, auth_srid, srtext, proj4text)
SELECT 900913 ,'EPSG',900913,'GEOGCS["WGS 84", DATUM["World Geodetic System
1984", SPHEROID["WGS 84", 6378137.0, 298.257223563,AUTHORITY["EPSG","7030"]], AUTHORITY["EPSG","6326"]],PRIMEM["Greenwich", 0.0, AUTHORITY["EPSG","8901"]], NIT["degree",0.017453292519943295], AXIS["Longitude", EAST], AXIS["Latitude", NORTH],AUTHORITY["EPSG","4326"]], PROJECTION["Mercator_1SP"],PARAMETER["semi_minor", 6378137.0],
PARAMETER["latitude_of_origin",0.0], PARAMETER["central_meridian", 0.0], PARAMETER["scale_factor",1.0], PARAMETER["false_easting", 0.0], PARAMETER["false_northing", 0.0],UNIT["m", 1.0], AXIS["x", EAST], AXIS["y", NORTH],AUTHORITY["EPSG","900913"]] |','+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0 +k=1.0 +units=m
+nadgrids=@null +no_defs'
WHERE NOT EXISTS(SELECT srid FROM spatial_ref_sys WHERE srid=900913);

EOF

# Run apache with the same id as the host user.
export APACHE_RUN_USER=$USER
export APACHE_RUN_GROUP=$GROUP
# Call the original entry point of the image to start apache.
docker-php-entrypoint apache2-foreground