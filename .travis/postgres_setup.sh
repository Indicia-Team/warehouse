psql -U postgres -d indicia <<EOF

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
ALTER USER indicia_report_user SET search_path = indicia, public, pg_catalog;

EOF
