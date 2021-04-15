#!/bin/sh

# Create indicia database
psql -U postgres -d postgres -c 'CREATE DATABASE indicia;'
# Configure indicia database
psql -U postgres -d indicia <<EOF
-- Add extension for PostGIS
CREATE EXTENSION postgis;
CREATE EXTENSION postgis_topology;
-- Add extension for btree_gin indexes.
CREATE EXTENSION btree_gin;
-- Create indicia_user and allocate permissions.
CREATE USER indicia_user WITH PASSWORD 'indicia_user_pass';
GRANT ALL PRIVILEGES ON DATABASE indicia TO indicia_user;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO indicia_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO indicia_user;
GRANT EXECUTE ON FUNCTION st_astext(geometry) TO indicia_user;
GRANT EXECUTE ON FUNCTION st_geomfromtext(text, integer) TO indicia_user;
GRANT EXECUTE ON FUNCTION st_transform(geometry, integer) TO indicia_user;
ALTER USER indicia_user SET search_path = indicia, public, pg_catalog;

-- Create indicia_report_user and allocate permissions.
CREATE USER indicia_report_user WITH PASSWORD 'indicia_report_user_pass';
GRANT SELECT ON ALL TABLES IN SCHEMA indicia TO indicia_report_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA indicia 
    GRANT SELECT ON TABLES TO indicia_report_user;
ALTER USER indicia_report_user SET search_path = indicia, public, pg_catalog;

EOF
