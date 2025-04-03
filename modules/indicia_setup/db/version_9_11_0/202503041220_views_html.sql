-- Escaping HTML now done in grid UI code rather than DB.

CREATE OR REPLACE VIEW gv_locations AS
 SELECT l.id,
    l.name,
    l.code,
    l.centroid_sref,
    lw.website_id,
    t.term AS type,
    CASE
      WHEN l.public = true THEN '<public>'::character varying
      ELSE COALESCE(w.title, '<none>'::character varying)
    END AS website,
    l.parent_id
   FROM locations l
     LEFT JOIN locations_websites lw ON l.id = lw.location_id and lw.deleted=false
     LEFT JOIN websites w ON w.id = lw.website_id AND w.deleted = false
     LEFT JOIN termlists_terms tlt ON tlt.id = l.location_type_id
     LEFT JOIN terms t ON t.id = tlt.term_id AND t.deleted = false
  WHERE l.deleted = false;
