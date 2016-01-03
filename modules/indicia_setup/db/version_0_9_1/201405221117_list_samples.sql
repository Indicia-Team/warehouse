CREATE OR REPLACE VIEW list_samples AS 
 SELECT s.id, su.title AS survey, l.name AS location, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, su.website_id, s.survey_id
   FROM samples s
   LEFT JOIN locations l ON s.location_id = l.id AND l.deleted = false
   JOIN surveys su ON s.survey_id = su.id AND su.deleted = false
  WHERE s.deleted = false;
