ALTER TABLE cache_occurrences ADD COLUMN map_sq_2km_id INTEGER;

-- create 2km index 

INSERT INTO map_squares (geom, x, y, size)
SELECT DISTINCT on (round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system)))),
    round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system)))),
    GREATEST(o.sensitivity_precision, 2000))    
  reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system),
  round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system)))),
  round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system)))),
  GREATEST(o.sensitivity_precision, 2000)
FROM samples s
JOIN occurrences o ON o.sample_id=s.id
LEFT JOIN map_squares msq 
  ON msq.x=round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system))))
  AND msq.y=round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, 2000), s.entered_sref_system))))
  AND msq.size=GREATEST(o.sensitivity_precision, 2000)
WHERE s.geom IS NOT NULL
AND msq.id IS NULL;

SELECT DISTINCT ON (o.confidential, o.sensitivity_precision, s.entered_sref, s.entered_sref_system) 
    s.geom, o.confidential, o.sensitivity_precision, s.entered_sref, s.entered_sref_system, 
    round(st_x(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 2000), s.entered_sref_system)))) as x2k,
    round(st_y(st_centroid(reduce_precision(s.geom, o.confidential, greatest(o.sensitivity_precision, 2000), s.entered_sref_system)))) as y2k,
    greatest(o.sensitivity_precision, 2000) as size2k,
    cast(null as integer) as msq_id2k
INTO temporary interim
FROM occurrences o
JOIN samples s on s.id=o.sample_id AND s.deleted=false
WHERE o.deleted=false;

UPDATE interim t SET msq_id2k=msq.id
FROM map_squares msq
WHERE msq.x=t.x2k
AND msq.y=t.y2k
AND msq.size=t.size2k;

UPDATE cache_occurrences co
SET map_sq_2km_id=t.msq_id2k
FROM interim t, occurrences o, samples s
WHERE o.id=co.id and s.id=co.sample_id
and t.confidential=o.confidential
and coalesce(t.sensitivity_precision, 0)=coalesce(o.sensitivity_precision, 0)
and t.entered_sref=s.entered_sref
and t.entered_sref_system=s.entered_sref_system;

DROP TABLE interim;

CREATE INDEX ix_cache_occurrences_map_sq_2km_id
  ON cache_occurrences
  USING btree
  (map_sq_2km_id);