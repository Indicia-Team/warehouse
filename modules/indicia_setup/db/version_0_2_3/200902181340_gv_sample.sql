-- View: gv_samples

-- DROP VIEW gv_samples;

CREATE OR REPLACE VIEW gv_samples AS 
 SELECT s.id, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, s.location_name, s.deleted, su.title, l.name AS location
   FROM samples s
   LEFT JOIN surveys su ON s.survey_id = su.id
   LEFT JOIN locations l ON s.location_id = l.id;


