-- View: gv_sample_comments

-- DROP VIEW gv_sample_comments;

CREATE OR REPLACE VIEW gv_sample_comments AS 
 SELECT sc.id, sc.comment, sc.sample_id, sc.email_address, sc.updated_on, sc.person_name, u.username, su.website_id
   FROM sample_comments sc
   JOIN samples s ON s.id = sc.sample_id AND s.deleted = false
   JOIN surveys su ON su.id=s.survey_id AND su.deleted=false
   LEFT JOIN users u ON sc.created_by_id = u.id
  WHERE sc.deleted = false;