-- View: gv_survey_comments

-- DROP VIEW gv_survey_comments;

CREATE OR REPLACE VIEW gv_survey_comments AS 
 SELECT sc.id, sc.comment, sc.survey_id, sc.email_address, sc.updated_on, sc.person_name, u.username, s.website_id
   FROM survey_comments sc
   JOIN surveys s ON s.id = sc.survey_id AND s.deleted = false
   LEFT JOIN users u ON sc.created_by_id = u.id
  WHERE sc.deleted = false;

-- View: detail_survey_comments

-- DROP VIEW detail_survey_comments;

CREATE OR REPLACE VIEW detail_survey_comments AS 
 SELECT sc.id, sc.comment, sc.survey_id, sc.email_address, sc.updated_on, sc.person_name, c.username, sc.created_on, sc.created_by_id, c.username AS created_by, sc.updated_by_id, u.username AS updated_by, s.website_id
   FROM survey_comments sc
   JOIN surveys s ON s.id = sc.survey_id AND s.deleted = false
   LEFT JOIN users c ON sc.created_by_id = c.id
   LEFT JOIN users u ON sc.updated_by_id = u.id
  WHERE sc.deleted = false;

-- View: list_survey_comments

-- DROP VIEW list_survey_comments;

CREATE OR REPLACE VIEW list_survey_comments AS 
 SELECT sc.id, sc.comment, sc.survey_id, sc.email_address, sc.updated_on, sc.person_name, u.username, s.website_id
   FROM survey_comments sc
   JOIN surveys s ON s.id = sc.survey_id AND s.deleted = false
   LEFT JOIN users u ON sc.created_by_id = u.id
  WHERE sc.deleted = false;

ALTER TABLE list_survey_comments
  OWNER TO indicia_user;

