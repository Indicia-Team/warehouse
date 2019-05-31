-- #slow script#

UPDATE cache_occurrences_nonfunctional onf
SET output_sref_system=get_output_system(
  reduce_precision(coalesce(s.geom, l.centroid_geom), o.confidential, greatest(o.sensitivity_precision, s.privacy_precision),
    case when s.entered_sref_system is null then l.centroid_sref_system else s.entered_sref_system end)
)
FROM occurrences o
JOIN samples s ON s.id=o.sample_id AND s.deleted=false
LEFT JOIN locations l ON l.id=s.location_id AND l.deleted=false
WHERE onf.id=o.id;