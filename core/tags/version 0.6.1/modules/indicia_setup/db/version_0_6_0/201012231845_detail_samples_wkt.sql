DROP VIEW detail_samples;

CREATE OR REPLACE VIEW detail_samples AS 
 SELECT s.id, s.entered_sref, s.entered_sref_system, s.geom, st_astext(s.geom) as wkt, s.location_name, s.date_start, s.date_end, s.date_type, s.location_id, l.name AS location, l.code AS location_code, s.created_by_id, c.username AS created_by, s.created_on, s.updated_by_id, u.username AS updated_by, s.updated_on, su.website_id, s.parent_id
   FROM samples s
   LEFT JOIN locations l ON l.id = s.location_id
   LEFT JOIN surveys su ON s.survey_id = su.id
   JOIN users c ON c.id = s.created_by_id
   JOIN users u ON u.id = s.updated_by_id
  WHERE s.deleted = false;

