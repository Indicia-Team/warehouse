CREATE OR REPLACE VIEW detail_samples AS 
 SELECT s.id, s.entered_sref, s.entered_sref_system, s.geom, st_astext(s.geom) AS wkt, s.location_name, s.date_start, s.date_end, s.date_type, s.location_id, l.name AS location, l.code AS location_code, s.created_by_id, c.username AS created_by, s.created_on, s.updated_by_id, u.username AS updated_by, s.updated_on, su.website_id, s.parent_id, s.comment, s.recorder_names, su.id AS survey_id, su.title AS survey_title, s.sample_method_id, s.external_key, s.group_id, g.title as group_title
   FROM samples s
   JOIN surveys su ON s.survey_id = su.id
   LEFT JOIN locations l ON l.id = s.location_id and l.deleted=false
   LEFT JOIN groups g ON g.id = s.group_id and g.deleted=false
   JOIN users c ON c.id = s.created_by_id
   JOIN users u ON u.id = s.updated_by_id
  WHERE s.deleted = false;