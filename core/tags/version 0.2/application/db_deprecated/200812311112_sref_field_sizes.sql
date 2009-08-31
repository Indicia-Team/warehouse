DROP VIEW detail_locations;
DROP VIEW list_locations;

ALTER TABLE locations ALTER centroid_sref TYPE character varying(40);

CREATE OR REPLACE VIEW detail_locations AS 
 SELECT l.id, l.name, l.code, l.parent_id, p.name AS parent, l.centroid_sref, l.centroid_sref_system, l.created_by_id, c.username AS created_by, l.updated_by_id, u.username AS updated_by
   FROM locations l
   JOIN users c ON c.id = l.created_by_id
   JOIN users u ON u.id = l.updated_by_id
   LEFT JOIN locations p ON p.id = l.parent_id;

CREATE OR REPLACE VIEW list_locations AS 
 SELECT l.id, l.name, l.code, l.centroid_sref
   FROM locations l;

DROP VIEW detail_samples;
DROP VIEW list_samples;

ALTER TABLE samples ALTER entered_sref TYPE character varying(40);

CREATE OR REPLACE VIEW detail_samples AS
SELECT s.*, su.title AS "survey", su.description
FROM samples s
JOIN surveys su ON s.survey_id = su.id;

CREATE OR REPLACE VIEW list_samples AS
SELECT s.date_start, s.date_end, s.date_type, s.entered_sref, s.location_name, s.comment, su.title, su.description
FROM samples s
JOIN surveys su ON s.survey_id = su.id;
