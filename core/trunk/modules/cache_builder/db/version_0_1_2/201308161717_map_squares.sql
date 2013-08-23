CREATE TABLE map_squares
(
  id serial NOT NULL,
  geom geometry(Polygon,900913) NOT NULL,
  x integer NOT NULL,
  y integer NOT NULL,
  size integer NOT NULL,
  CONSTRAINT pk_map_squares PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

COMMENT ON TABLE map_squares IS 'Distinct list of grid squares in use for records which can be used for report aggregation.';
COMMENT ON COLUMN map_squares.id IS 'Unique identifier for the map square.';
COMMENT ON COLUMN map_squares.geom IS 'Geometry of the square.';
COMMENT ON COLUMN map_squares.x IS 'X coordinate for the square centroid.';
COMMENT ON COLUMN map_squares.y IS 'Y coordinate for the square centroid.';
COMMENT ON COLUMN map_squares.size IS 'Size of the square in metres (or projection units)';

ALTER TABLE cache_occurrences ADD COLUMN map_sq_1km_id INTEGER;
ALTER TABLE cache_occurrences ADD COLUMN map_sq_10km_id INTEGER;

-- create 1km index 

INSERT INTO map_squares (geom, x, y, size)
SELECT DISTINCT on (round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 1000), s.entered_sref_system)))),
    round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 1000), s.entered_sref_system)))),
    GREATEST(o.sensitivity_precision, 1000))    
  reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 1000), s.entered_sref_system),
  round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 1000), s.entered_sref_system)))),
  round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 1000), s.entered_sref_system)))),
  GREATEST(o.sensitivity_precision, 1000)
FROM samples s
JOIN occurrences o ON o.sample_id=s.id
WHERE s.geom IS NOT NULL;

-- create 10km index 

INSERT INTO map_squares (geom, x, y, size)
SELECT DISTINCT on (round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system)))),
    round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system)))),
    GREATEST(o.sensitivity_precision, 10000))    
  reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system),
  round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system)))),
  round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system)))),
  GREATEST(o.sensitivity_precision, 10000)
FROM samples s
JOIN occurrences o ON o.sample_id=s.id
-- left join to ensure no duplicates, as 1km index includes 10km squares for sensitive records
LEFT JOIN map_squares msq 
	ON msq.x=round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system))))
	AND msq.y=round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system))))
	AND msq.size=GREATEST(o.sensitivity_precision, 10000)
WHERE s.geom IS NOT NULL
AND msq.id IS NULL;

CREATE UNIQUE INDEX ix_map_squares_unique
   ON map_squares (x ASC NULLS LAST, y ASC NULLS LAST, size ASC NULLS LAST);

UPDATE cache_occurrences co
SET map_sq_1km_id=msq.id
FROM map_squares msq, samples s, occurrences o
WHERE s.id=co.sample_id AND o.id=co.id
AND msq.x=round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 1000), s.entered_sref_system))))
AND msq.y=round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 1000), s.entered_sref_system))))
AND msq.size=greatest(o.sensitivity_precision, 1000);

CREATE INDEX ix_cache_occurrences_map_sq_1km_id
  ON cache_occurrences
  USING btree
  (map_sq_1km_id);

UPDATE cache_occurrences co
SET map_sq_10km_id=msq.id
FROM map_squares msq, samples s, occurrences o
WHERE s.id=co.sample_id AND o.id=co.id
AND msq.x=round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 10000), s.entered_sref_system))))
AND msq.y=round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 10000), s.entered_sref_system))))
AND msq.size=greatest(o.sensitivity_precision, 10000);

CREATE INDEX ix_cache_occurrences_map_sq_10km_id
  ON cache_occurrences
  USING btree
  (map_sq_1km_id);