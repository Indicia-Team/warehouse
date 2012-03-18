DROP VIEW IF EXISTS gv_locations;

CREATE OR REPLACE VIEW gv_locations AS 
 SELECT l.id, l.name, l.code, l.centroid_sref, lw.website_id, w.title AS website
   FROM locations l
   LEFT JOIN locations_websites lw ON l.id = lw.location_id
   JOIN websites w ON w.id = lw.website_id
  WHERE l.deleted = false
   AND w.deleted = false;

