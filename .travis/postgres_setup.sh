psql -U postgres <<EOF

-- Create a blank Indicia database.
CREATE DATABASE indicia;
\connect indicia;

-- Activate PostGIS (version 2.1).
CREATE EXTENSION postgis;
CREATE EXTENSION postgis_topology;

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
 
EOF
