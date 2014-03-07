-- #slow script#

-- create 1km index 

INSERT INTO map_squares (geom, x, y, size)
SELECT DISTINCT on (
      round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
          GREATEST(o.sensitivity_precision, 1000), coalesce(s.entered_sref_system, l.centroid_sref_system))))),
      round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
          GREATEST(o.sensitivity_precision, 1000), coalesce(s.entered_sref_system, l.centroid_sref_system))))),
      GREATEST(o.sensitivity_precision, 1000)
  )
  reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
      GREATEST(o.sensitivity_precision, 1000), coalesce(s.entered_sref_system, l.centroid_sref_system)),
  round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
      GREATEST(o.sensitivity_precision, 1000), coalesce(s.entered_sref_system, l.centroid_sref_system))))),
  round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
      GREATEST(o.sensitivity_precision, 1000), coalesce(s.entered_sref_system, l.centroid_sref_system))))),
  GREATEST(o.sensitivity_precision, 1000)
FROM samples s
JOIN occurrences o ON o.sample_id=s.id
LEFT JOIN locations l on l.id=s.location_id AND l.deleted=false
WHERE coalesce(s.geom, l.centroid_geom) IS NOT NULL;

-- create 2km index

SELECT DISTINCT on (
      round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
          GREATEST(o.sensitivity_precision, 2000), coalesce(s.entered_sref_system, l.centroid_sref_system))))),
      round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
          GREATEST(o.sensitivity_precision, 2000), coalesce(s.entered_sref_system, l.centroid_sref_system))))),
      GREATEST(o.sensitivity_precision, 2000)
  )
  reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
      GREATEST(o.sensitivity_precision, 2000), coalesce(s.entered_sref_system, l.centroid_sref_system)) as geom,
  round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
      GREATEST(o.sensitivity_precision, 2000), coalesce(s.entered_sref_system, l.centroid_sref_system))))) as x,
  round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
      GREATEST(o.sensitivity_precision, 2000), coalesce(s.entered_sref_system, l.centroid_sref_system))))) as y,
  GREATEST(o.sensitivity_precision, 2000) as size
INTO temp
FROM samples s
JOIN occurrences o ON o.sample_id=s.id
LEFT JOIN locations l on l.id=s.location_id AND l.deleted=false
WHERE coalesce(s.geom, l.centroid_geom) IS NOT NULL;

DELETE FROM temp 
USING map_squares msq
WHERE msq.x=temp.x AND msq.y=temp.y AND msq.size=temp.size;

INSERT INTO map_squares (geom, x, y, size) SELECT * FROM temp;

DROP TABLE temp;

-- create 10km index 

SELECT DISTINCT on (
      round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
          GREATEST(o.sensitivity_precision, 10000), coalesce(s.entered_sref_system, l.centroid_sref_system))))),
      round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
          GREATEST(o.sensitivity_precision, 10000), coalesce(s.entered_sref_system, l.centroid_sref_system))))),
      GREATEST(o.sensitivity_precision, 10000)
  )
  reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
      GREATEST(o.sensitivity_precision, 10000), coalesce(s.entered_sref_system, l.centroid_sref_system)) as geom,
  round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
      GREATEST(o.sensitivity_precision, 10000), coalesce(s.entered_sref_system, l.centroid_sref_system))))) as x,
  round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
      GREATEST(o.sensitivity_precision, 10000), coalesce(s.entered_sref_system, l.centroid_sref_system))))) as y,
  GREATEST(o.sensitivity_precision, 10000) as size
INTO temp
FROM samples s
JOIN occurrences o ON o.sample_id=s.id
LEFT JOIN locations l on l.id=s.location_id AND l.deleted=false
WHERE coalesce(s.geom, l.centroid_geom) IS NOT NULL;

DELETE FROM temp 
USING map_squares msq
WHERE msq.x=temp.x AND msq.y=temp.y AND msq.size=temp.size;

INSERT INTO map_squares (geom, x, y, size) SELECT * FROM temp;

DROP TABLE temp;

SELECT DISTINCT ON (o.confidential, o.sensitivity_precision, coalesce(s.entered_sref, l.centroid_sref), coalesce(s.entered_sref_system, l.centroid_sref_system)) 
    coalesce(s.geom, l.centroid_geom) as geom, o.confidential, o.sensitivity_precision, 
    coalesce(s.entered_sref, l.centroid_sref) as entered_sref, coalesce(s.entered_sref_system, l.centroid_sref_system) as entered_sref_system, 
    round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
        greatest(o.sensitivity_precision, 1000), coalesce(s.entered_sref_system, l.centroid_sref_system))))) as x1k,
    round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
        greatest(o.sensitivity_precision, 1000), coalesce(s.entered_sref_system, l.centroid_sref_system))))) as y1k,
    greatest(o.sensitivity_precision, 1000) as size1k,
    round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
        greatest(o.sensitivity_precision, 2000), coalesce(s.entered_sref_system, l.centroid_sref_system))))) as x2k,
    round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
        greatest(o.sensitivity_precision, 2000), coalesce(s.entered_sref_system, l.centroid_sref_system))))) as y2k,
    greatest(o.sensitivity_precision, 2000) as size2k,    
    round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
        greatest(o.sensitivity_precision, 10000), coalesce(s.entered_sref_system, l.centroid_sref_system))))) as x10k,
    round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, 
        greatest(o.sensitivity_precision, 10000), coalesce(s.entered_sref_system, l.centroid_sref_system))))) as y10k,
    greatest(o.sensitivity_precision, 10000) as size10k,
    cast(null as integer) as msq_id1k, cast(null as integer) as msq_id2k, cast(null as integer) as msq_id10k
INTO temporary interim
FROM occurrences o
JOIN samples s on s.id=o.sample_id AND s.deleted=false
LEFT JOIN locations l on l.id=s.location_id AND l.deleted=false
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

UPDATE interim SET sensitivity_precision=0 WHERE sensitivity_precision IS NULL;

SELECT o.id, t.msq_id1k, t.msq_id2k, t.msq_id10k
INTO interim2
FROM samples s
JOIN occurrences o ON o.sample_id=s.id and o.deleted=false
LEFT JOIN locations l on l.id=s.location_id AND l.deleted=false
JOIN interim t ON t.entered_sref=coalesce(s.entered_sref, l.centroid_sref)
  AND t.entered_sref_system=coalesce(s.entered_sref_system, l.centroid_sref_system)
  AND t.confidential=o.confidential
  AND t.sensitivity_precision=COALESCE(o.sensitivity_precision, 0)
where s.deleted=false;

CREATE INDEX ix_interim2 ON interim2(id);

UPDATE cache_occurrences co
SET map_sq_1km_id=t.msq_id1k, map_sq_2km_id=t.msq_id2k, map_sq_10km_id=t.msq_id10k
FROM interim2 t
WHERE t.id=co.id;

DROP TABLE interim;
DROP TABLE interim2;

CREATE INDEX ix_cache_occurrences_map_sq_1km_id
  ON cache_occurrences
  USING btree
  (map_sq_1km_id);
  
CREATE INDEX ix_cache_occurrences_map_sq_2km_id
  ON cache_occurrences
  USING btree
  (map_sq_2km_id);

CREATE INDEX ix_cache_occurrences_map_sq_10km_id
  ON cache_occurrences
  USING btree
  (map_sq_1km_id);