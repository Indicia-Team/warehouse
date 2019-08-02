CREATE OR REPLACE VIEW list_locations AS
 SELECT l.id,
    l.name,
    l.code,
    l.centroid_sref,
    lw.website_id,
    l.location_type_id,
    l.parent_id
   FROM locations l
     LEFT JOIN locations_websites lw ON l.id = lw.location_id AND lw.deleted = false
  WHERE l.deleted = false;