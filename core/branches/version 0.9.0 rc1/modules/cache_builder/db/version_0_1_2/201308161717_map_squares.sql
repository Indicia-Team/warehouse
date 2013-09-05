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
LEFT JOIN map_squares msq 
	ON msq.x=round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system))))
	AND msq.y=round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system))))
	AND msq.size=GREATEST(o.sensitivity_precision, 10000)
WHERE s.geom IS NOT NULL
AND msq.id IS NULL;

CREATE UNIQUE INDEX ix_map_squares_unique
   ON map_squares (x ASC NULLS LAST, y ASC NULLS LAST, size ASC NULLS LAST);

SELECT DISTINCT ON (o.confidential, o.sensitivity_precision, s.entered_sref, s.entered_sref_system) 
    s.geom, o.confidential, o.sensitivity_precision, s.entered_sref, s.entered_sref_system, 
    round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 1000), s.entered_sref_system)))) as x1k,
    round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 1000), s.entered_sref_system)))) as y1k,
    greatest(o.sensitivity_precision, 1000) as size1k,
    round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 10000), s.entered_sref_system)))) as x10k,
    round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 10000), s.entered_sref_system)))) as y10k,
    greatest(o.sensitivity_precision, 10000) as size10k,
    cast(null as integer) as msq_id1k, cast(null as integer) as msq_id10k
INTO temporary interim
FROM occurrences o
JOIN samples s on s.id=o.sample_id AND s.deleted=false
WHERE o.deleted=false;

UPDATE interim t SET msq_id1k=msq.id
FROM map_squares msq
WHERE msq.x=t.x1k
AND msq.y=t.y1k
AND msq.size=t.size1k;

UPDATE interim t SET msq_id10k=msq.id
FROM map_squares msq
WHERE msq.x=t.x10k
AND msq.y=t.y10k
AND msq.size=t.size10k;

UPDATE cache_occurrences co
SET map_sq_1km_id=t.msq_id1k, map_sq_10km_id=t.msq_id10k
FROM interim t, occurrences o, samples s
WHERE o.id=co.id and s.id=co.sample_id
and t.confidential=o.confidential
and coalesce(t.sensitivity_precision, 0)=coalesce(o.sensitivity_precision, 0)
and t.entered_sref=s.entered_sref
and t.entered_sref_system=s.entered_sref_system;

DROP TABLE interim;

CREATE INDEX ix_cache_occurrences_map_sq_1km_id
  ON cache_occurrences
  USING btree
  (map_sq_1km_id);

CREATE INDEX ix_cache_occurrences_map_sq_10km_id
  ON cache_occurrences
  USING btree
  (map_sq_1km_id);