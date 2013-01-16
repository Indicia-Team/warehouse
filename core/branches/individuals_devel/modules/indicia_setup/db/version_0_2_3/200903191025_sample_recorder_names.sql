ALTER TABLE samples ADD COLUMN recorder_names character varying;
ALTER TABLE samples ALTER COLUMN recorder_names SET STORAGE EXTENDED;
COMMENT ON COLUMN samples.recorder_names IS 'List of names of the people who were involved in recording of this sample, one per line. Used when the recorders are not listed in the people table.';

DROP VIEW gv_samples;

CREATE OR REPLACE VIEW gv_samples AS
 SELECT s.id, s.date_start, s.date_end, s.date_type, s.entered_sref, s.entered_sref_system, s.location_name, s.deleted, su.title, l.name AS location, s.recorder_names
   FROM samples s
   LEFT JOIN surveys su ON s.survey_id = su.id
   LEFT JOIN locations l ON s.location_id = l.id;