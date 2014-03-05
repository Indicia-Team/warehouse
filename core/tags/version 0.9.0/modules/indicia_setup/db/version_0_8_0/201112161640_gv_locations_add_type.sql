DROP VIEW gv_locations;

CREATE OR REPLACE VIEW gv_locations AS 
 SELECT l.id, l.name, l.code, l.centroid_sref, lw.website_id, w.title AS website, t.term AS type
   FROM locations l
   LEFT JOIN locations_websites lw ON l.id = lw.location_id
   LEFT JOIN websites w ON w.id = lw.website_id AND w.deleted = false
   LEFT JOIN termlists_terms tlt ON tlt.id = l.location_type_id
   LEFT JOIN terms t ON t.id = tlt.term_id AND t.deleted = false
  WHERE l.deleted = false;
