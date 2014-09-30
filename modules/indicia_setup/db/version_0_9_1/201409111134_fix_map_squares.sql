-- #slow script#

-- Find all the broken occurrence records where the map square links don't match what we now expect
select distinct o.id, cast(null as integer) as msq_1k_id, cast(null as integer) as msq_2k_id, cast(null as integer) as msq_10k_id,
  round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 1000), s.entered_sref_system)))) as x1000,
  round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 1000), s.entered_sref_system)))) as y1000,
  GREATEST(o.sensitivity_precision, s.privacy_precision, 1000) as size1000,
  round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 2000), s.entered_sref_system)))) as x2000,
  round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 2000), s.entered_sref_system)))) as y2000,
  GREATEST(o.sensitivity_precision, s.privacy_precision, 2000) as size2000,
  round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 10000), s.entered_sref_system)))) as x10000,
  round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 10000), s.entered_sref_system)))) as y10000,
  GREATEST(o.sensitivity_precision, s.privacy_precision, 10000) as size10000
into temporary tofix
from cache_occurrences o
join occurrences occ on occ.id=o.id
join samples s on s.id=o.sample_id
left join locations l on l.id=s.location_id
join map_squares msq1 on msq1.id=map_sq_1km_id
join map_squares msq2 on msq2.id=map_sq_2km_id
join map_squares msq10 on msq10.id=map_sq_10km_id
where msq1.x <> round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 1000), s.entered_sref_system))))
OR msq1.y <> round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 1000), s.entered_sref_system))))
OR msq2.x <> round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 2000), s.entered_sref_system))))
OR msq2.y <> round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 2000), s.entered_sref_system))))
OR msq10.x <> round(st_x(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 10000), s.entered_sref_system))))
OR msq10.y <> round(st_y(st_centroid(reduce_precision(coalesce(s.geom, l.centroid_geom), occ.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 10000), s.entered_sref_system))));

-- fill in the map square IDs for any where the squares already exist
update tofix
set msq_1k_id=msq.id
from map_squares msq 
where msq.x=tofix.x1000 and msq.y=tofix.y1000 and msq.size=tofix.size1000;

update tofix
set msq_2k_id=msq.id
from map_squares msq 
where msq.x=tofix.x2000 and msq.y=tofix.y2000 and msq.size=tofix.size2000;

update tofix
set msq_10k_id=msq.id
from map_squares msq 
where msq.x=tofix.x10000 and msq.y=tofix.y10000 and msq.size=tofix.size10000;

-- THE FOLLOWING VIOLATES UNIQUE INDEXES BUT IT SHOULDN'T Probably because the square size is set to 1000 in one case, but GREATES(...) in another?
/*
Thw square size for msq_1km_id might point to a map square for 10000 size if it is sensitive etc. 
*/

-- create the missing squares
INSERT INTO map_squares (geom, x, y, size)
SELECT distinct reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 1000), s.entered_sref_system), 
    tofix.x1000, tofix.y1000, GREATEST(o.sensitivity_precision, s.privacy_precision, 1000)
FROM tofix
join occurrences o on o.id=tofix.id
join samples s on s.id=o.sample_id
where tofix.msq_1k_id is null;

INSERT INTO map_squares (geom, x, y, size)
SELECT distinct reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 2000), s.entered_sref_system), 
    tofix.x2000, tofix.y2000, GREATEST(o.sensitivity_precision, s.privacy_precision, 2000)
FROM tofix
join occurrences o on o.id=tofix.id
join samples s on s.id=o.sample_id
left join map_squares msq on msq.x=tofix.x2000 and msq.y=tofix.y2000 and msq.size=GREATEST(o.sensitivity_precision, s.privacy_precision, 2000)
where tofix.msq_2k_id is null and msq.id is null and s.geom is not null;

INSERT INTO map_squares (geom, x, y, size)
SELECT distinct reduce_precision(s.geom, o.confidential, GREATEST(o.sensitivity_precision, s.privacy_precision, 10000), s.entered_sref_system), 
    tofix.x10000, tofix.y10000, GREATEST(o.sensitivity_precision, s.privacy_precision, 10000)
FROM tofix
join occurrences o on o.id=tofix.id
join samples s on s.id=o.sample_id
left join map_squares msq on msq.x=tofix.x10000 and msq.y=tofix.y10000 and msq.size=GREATEST(o.sensitivity_precision, s.privacy_precision, 10000)
where tofix.msq_10k_id is null and msq.id is null and s.geom is not null;

-- now link up the missing squares
update tofix
set msq_1k_id=msq.id
from map_squares msq 
where msq.x=tofix.x1000 and msq.y=tofix.y1000 and msq.size=tofix.size1000
and msq_1k_id is null;

update tofix
set msq_2k_id=msq.id
from map_squares msq 
where msq.x=tofix.x2000 and msq.y=tofix.y2000 and msq.size=tofix.size2000
and msq_2k_id is null;

update tofix
set msq_10k_id=msq.id
from map_squares msq 
where msq.x=tofix.x10000 and msq.y=tofix.y10000 and msq.size=tofix.size10000
and msq_10k_id is null;

-- copy the fixes across to cache_occurrences
update cache_occurrences o
set map_sq_1km_id=tofix.msq_1k_id, map_sq_2km_id=tofix.msq_2k_id, map_sq_10km_id=tofix.msq_10k_id
from tofix
where tofix.id=o.id;

-- remove any unwanted map square records
delete from map_squares 
where id in (
select msq.id from map_squares msq
left join cache_occurrences o on o.map_sq_1km_id=msq.id
where o.id is null
) and id in (
select msq.id from map_squares msq
left join cache_occurrences o on o.map_sq_2km_id=msq.id
where o.id is null
) and id in (
select msq.id from map_squares msq
left join cache_occurrences o on o.map_sq_10km_id=msq.id
where o.id is null
);