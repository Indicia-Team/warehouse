-- #slow script#

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

-- create 2km index

SELECT DISTINCT on (round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system)))),
    round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system)))),
    GREATEST(o.sensitivity_precision, 2000))    
  reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system) as geom,
  round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system)))) as x,
  round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system)))) as y,
  GREATEST(o.sensitivity_precision, 2000) as size
INTO temp
FROM samples s
JOIN occurrences o ON o.sample_id=s.id
WHERE s.geom IS NOT NULL;

DELETE FROM temp 
USING map_squares msq
WHERE msq.x=temp.x AND msq.y=temp.y AND msq.size=temp.size;

INSERT INTO map_squares (geom, x, y, size) SELECT * FROM temp;

DROP TABLE temp;

-- create 10km index 

SELECT DISTINCT on (round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system)))),
    round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system)))),
    GREATEST(o.sensitivity_precision, 10000))    
  reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system) as geom,
  round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system)))) as x,
  round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 10000), s.entered_sref_system)))) as y,
  GREATEST(o.sensitivity_precision, 10000) as size
INTO temp
FROM samples s
JOIN occurrences o ON o.sample_id=s.id
WHERE s.geom IS NOT NULL;

DELETE FROM temp 
USING map_squares msq
WHERE msq.x=temp.x AND msq.y=temp.y AND msq.size=temp.size;

INSERT INTO map_squares (geom, x, y, size) SELECT * FROM temp;

DROP TABLE temp;

SELECT DISTINCT ON (o.confidential, o.sensitivity_precision, s.entered_sref, s.entered_sref_system) 
    s.geom, o.confidential, o.sensitivity_precision, s.entered_sref, s.entered_sref_system, 
    round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 1000), s.entered_sref_system)))) as x1k,
    round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 1000), s.entered_sref_system)))) as y1k,
    greatest(o.sensitivity_precision, 1000) as size1k,
    round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 2000), s.entered_sref_system)))) as x2k,
    round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 2000), s.entered_sref_system)))) as y2k,
    greatest(o.sensitivity_precision, 2000) as size2k,    
    round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 10000), s.entered_sref_system)))) as x10k,
    round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 10000), s.entered_sref_system)))) as y10k,
    greatest(o.sensitivity_precision, 10000) as size10k,
    cast(null as integer) as msq_id1k, cast(null as integer) as msq_id2k, cast(null as integer) as msq_id10k
INTO temporary interim
FROM occurrences o
JOIN samples s on s.id=o.sample_id AND s.deleted=false
WHERE o.deleted=false;

UPDATE interim t SET msq_id1k=msq.id
FROM map_squares msq
WHERE msq.x=t.x1k
AND msq.y=t.y1k
AND msq.size=t.size1k;

UPDATE interim t SET msq_id2k=msq.id
FROM map_squares msq
WHERE msq.x=t.x2k
AND msq.y=t.y2k
AND msq.size=t.size2k;

UPDATE interim t SET msq_id10k=msq.id
FROM map_squares msq
WHERE msq.x=t.x10k
AND msq.y=t.y10k
AND msq.size=t.size10k;

UPDATE cache_occurrences co
SET map_sq_1km_id=t.msq_id1k, map_sq_2km_id=t.msq_id2k, map_sq_10km_id=t.msq_id10k
FROM interim t, occurrences o, samples s
WHERE o.id=co.id and s.id=co.sample_id
and t.confidential=o.confidential
and coalesce(t.sensitivity_precision, 0)=coalesce(o.sensitivity_precision, 0)
and t.entered_sref=s.entered_sref
and t.entered_sref_system=s.entered_sref_system;

DROP TABLE interim;