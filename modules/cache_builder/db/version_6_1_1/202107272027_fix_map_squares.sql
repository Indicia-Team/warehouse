-- #slow script#

DROP TABLE IF EXISTS to_fix;

-- Find all the samples WHERE the map square data was skipped due to not containing occurrences.
SELECT DISTINCT s.id, cast(null AS integer) AS msq_1k_id, cast(null AS integer) AS msq_2k_id, cast(null AS integer) AS msq_10k_id,
  GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer, max(o.sensitivity_precision), s.privacy_precision, 1000) AS size1000,
  round(st_x(st_centroid(reduce_precision(
    coalesce(s.geom, l.centroid_geom), bool_or(o.confidential),
    GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer, max(o.sensitivity_precision), s.privacy_precision, 1000))
  ))) AS x1000,
  round(st_y(st_centroid(reduce_precision(
    coalesce(s.geom, l.centroid_geom), bool_or(o.confidential),
    GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer, max(o.sensitivity_precision), s.privacy_precision, 1000))
  ))) AS y1000,
  GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer, max(o.sensitivity_precision), s.privacy_precision, 2000) AS size2000,
  round(st_x(st_centroid(reduce_precision(
    coalesce(s.geom, l.centroid_geom), bool_or(o.confidential),
    GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer, max(o.sensitivity_precision), s.privacy_precision, 2000))
  ))) AS x2000,
  round(st_y(st_centroid(reduce_precision(
    coalesce(s.geom, l.centroid_geom), bool_or(o.confidential),
    GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer, max(o.sensitivity_precision), s.privacy_precision, 2000))
  ))) AS y2000,
  GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer, max(o.sensitivity_precision), s.privacy_precision, 10000) AS size10000,
  round(st_x(st_centroid(reduce_precision(
    coalesce(s.geom, l.centroid_geom), bool_or(o.confidential),
    GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer, max(o.sensitivity_precision), s.privacy_precision, 10000))
  ))) AS x10000,
  round(st_y(st_centroid(reduce_precision(
    coalesce(s.geom, l.centroid_geom), bool_or(o.confidential),
    GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(s.entered_sref_system)))))::integer, max(o.sensitivity_precision), s.privacy_precision, 10000))
  ))) AS y10000
INTO temporary to_fix
FROM cache_samples_functional sf
JOIN samples s ON s.id=sf.id
LEFT JOIN samples sc ON sc.parent_id=s.id
-- check occurrence sensitivity for this sample AND  children.
LEFT JOIN occurrences o ON (o.sample_id=s.id OR o.sample_id=sc.id) AND o.deleted=false
LEFT JOIN locations l ON l.id=s.location_id
WHERE sf.map_sq_10km_id IS NULL
GROUP BY s.id, l.centroid_geom;

-- fill in the map square IDs for any WHERE the squares already exist
UPDATE to_fix
SET msq_1k_id=msq.id
FROM map_squares msq
WHERE msq.x=to_fix.x1000 AND msq.y=to_fix.y1000 AND msq.size=to_fix.size1000;

UPDATE to_fix
SET msq_2k_id=msq.id
FROM map_squares msq
WHERE msq.x=to_fix.x2000 AND msq.y=to_fix.y2000 AND msq.size=to_fix.size2000;

UPDATE to_fix
SET msq_10k_id=msq.id
FROM map_squares msq
WHERE msq.x=to_fix.x10000 AND msq.y=to_fix.y10000 AND msq.size=to_fix.size10000;

-- copy the fixes across to cache_samples_functional
UPDATE cache_samples_functional s
SET map_sq_1km_id=to_fix.msq_1k_id, map_sq_2km_id=to_fix.msq_2k_id, map_sq_10km_id=to_fix.msq_10k_id
FROM to_fix
WHERE to_fix.id=s.id;

DROP TABLE to_fix;