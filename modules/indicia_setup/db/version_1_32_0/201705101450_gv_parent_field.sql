-- drops required as adding parent_id field.
DROP VIEW gv_locations;
CREATE VIEW gv_locations AS
 SELECT l.id,
    l.name,
    l.code,
    l.centroid_sref,
    lw.website_id,
    t.term AS type,
    CASE
      WHEN l.public = true THEN '&lt;public&gt;'::character varying
      ELSE COALESCE(w.title, '&lt;none&gt;'::character varying)
    END AS website,
    l.parent_id
   FROM locations l
     LEFT JOIN locations_websites lw ON l.id = lw.location_id and lw.deleted=false
     LEFT JOIN websites w ON w.id = lw.website_id AND w.deleted = false
     LEFT JOIN termlists_terms tlt ON tlt.id = l.location_type_id
     LEFT JOIN terms t ON t.id = tlt.term_id AND t.deleted = false
  WHERE l.deleted = false;

CREATE OR REPLACE VIEW gv_samples AS 
 SELECT s.id,
     s.date_start,
     s.date_end,
     s.date_type,
     s.entered_sref,
     s.entered_sref_system,
     s.location_name,
     s.deleted,
     su.title AS survey,
     w.title AS website,
     l.name AS location,
     su.website_id,
     s.parent_id,
     s.location_id
   FROM samples s
   JOIN surveys su ON s.survey_id = su.id AND su.deleted=false
   JOIN websites w ON w.id = su.website_id AND w.deleted=false
   LEFT JOIN locations l ON s.location_id = l.id
   WHERE s.deleted=false;
