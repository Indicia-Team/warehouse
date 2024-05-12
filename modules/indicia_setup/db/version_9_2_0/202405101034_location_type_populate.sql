UPDATE index_groups_locations igl
SET location_type_id=l.location_type_id
FROM locations l
WHERE l.id=igl.location_id;