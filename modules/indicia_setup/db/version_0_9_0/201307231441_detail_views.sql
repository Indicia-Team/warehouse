CREATE OR REPLACE VIEW detail_people AS 
 SELECT p.id, p.first_name, p.surname, p.initials, p.email_address, p.website_url, p.external_key, p.created_by_id, c.username AS created_by, p.updated_by_id, u.username AS updated_by, uw.website_id, p.created_on, p.updated_on
   FROM people p
   JOIN users c ON c.id = p.created_by_id
   JOIN users u ON u.id = p.updated_by_id
   LEFT JOIN users us ON us.person_id = p.id
   LEFT JOIN users_websites uw ON uw.user_id = us.id AND uw.site_role_id IS NOT NULL
  WHERE p.deleted = false;

CREATE OR REPLACE VIEW detail_users AS 
 SELECT u.id, u.username, uw.website_id, u.person_id, u.created_by_id, cu.username AS created_by, u.updated_by_id, u.created_on, u.updated_on, uu.username AS updated_by
   FROM users u
   JOIN users cu ON cu.id = u.created_by_id
   JOIN users uu ON uu.id = u.updated_by_id
   JOIN users_websites uw ON u.id = uw.user_id AND uw.site_role_id IS NOT NULL
  WHERE u.deleted = false;

CREATE OR REPLACE VIEW detail_occurrence_comments AS 
 SELECT oc.id, oc.comment, oc.occurrence_id, oc.email_address, oc.updated_on, oc.person_name, c.username, o.website_id, oc.created_on, oc.created_by_id, c.username AS created_by, oc.updated_by_id, u.username AS updated_by
   FROM occurrence_comments oc
   JOIN occurrences o ON o.id = oc.occurrence_id AND o.deleted = false
   LEFT JOIN users c ON oc.created_by_id = c.id
   LEFT JOIN users u ON oc.updated_by_id = u.id 
  WHERE oc.deleted = false;

CREATE OR REPLACE VIEW detail_sample_comments AS 
 SELECT sc.id, sc.comment, sc.sample_id, sc.email_address, sc.updated_on, sc.person_name, c.username, sc.created_on, sc.created_by_id, c.username AS created_by, sc.updated_by_id, u.username AS updated_by, su.website_id
   FROM sample_comments sc
   JOIN samples s ON s.id = sc.sample_id AND s.deleted = false
   LEFT JOIN users c ON sc.created_by_id = c.id
   LEFT JOIN users u ON sc.updated_by_id = u.id 
   JOIN surveys su ON su.id = s.survey_id
  WHERE sc.deleted = false;