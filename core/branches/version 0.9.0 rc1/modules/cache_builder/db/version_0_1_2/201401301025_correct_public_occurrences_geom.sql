-- Set the SRID for cache_occurrences.

CREATE OR REPLACE function f_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

-- PostGIS 2 allows a simple typemod on the geometry columns.
IF PostGIS_full_version() LIKE 'POSTGIS="2%' THEN
	ALTER TABLE cache_occurrences ALTER COLUMN public_geom TYPE geometry(Geometry,900913);
ELSE
	ALTER TABLE cache_occurrences
		ADD CONSTRAINT enforce_dims_geom CHECK (st_ndims(public_geom) = 2);
	ALTER TABLE cache_occurrences
		ADD CONSTRAINT enforce_srid_geom CHECK (st_srid(public_geom) = 900913);
END IF;



END
$func$;

SELECT f_ddl();

DROP FUNCTION f_ddl();

