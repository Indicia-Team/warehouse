CREATE OR REPLACE function f_add_ddl (OUT success bool)
    LANGUAGE plpgsql AS
$func$
BEGIN 
  
success := TRUE;

BEGIN
CREATE TABLE map_squares
(
  id serial NOT NULL,
  geom geometry NOT NULL,
  x integer NOT NULL,
  y integer NOT NULL,
  size integer NOT NULL,
  CONSTRAINT pk_map_squares PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

ALTER TABLE map_squares ADD CONSTRAINT enforce_map_squares_geom_polygon CHECK (geometrytype(geom) = 'POLYGON'::text OR geom IS NULL);
ALTER TABLE map_squares ADD CONSTRAINT enforce_map_squares_geom_900913 CHECK (st_srid(geom) = 900913);
ALTER TABLE map_squares ADD CONSTRAINT enforce_map_squares_geom_dims CHECK (st_ndims(geom) = 2);

EXCEPTION
    WHEN duplicate_table THEN 
      RAISE NOTICE 'table exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN map_sq_1km_id INTEGER;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN map_sq_2km_id INTEGER;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

BEGIN
ALTER TABLE cache_occurrences ADD COLUMN map_sq_10km_id INTEGER;
EXCEPTION
    WHEN duplicate_column THEN 
      RAISE NOTICE 'column exists.';
      success := FALSE;
END;

END
$func$;

SELECT f_add_ddl();

DROP FUNCTION f_add_ddl();

COMMENT ON TABLE map_squares IS 'Distinct list of grid squares in use for records which can be used for report aggregation.';
COMMENT ON COLUMN map_squares.id IS 'Unique identifier for the map square.';
COMMENT ON COLUMN map_squares.geom IS 'Geometry of the square.';
COMMENT ON COLUMN map_squares.x IS 'X coordinate for the square centroid.';
COMMENT ON COLUMN map_squares.y IS 'Y coordinate for the square centroid.';
COMMENT ON COLUMN map_squares.size IS 'Size of the square in metres (or projection units)';

DROP INDEX IF EXISTS ix_map_squares_unique;
CREATE UNIQUE INDEX ix_map_squares_unique
   ON map_squares (x ASC NULLS LAST, y ASC NULLS LAST, size ASC NULLS LAST);
