DROP VIEW gv_surveys;

CREATE OR REPLACE VIEW gv_surveys AS 
 SELECT s.id, s.title, s.description, w.title AS website, s.deleted
   FROM surveys s
   LEFT JOIN websites w ON s.website_id = w.id;

DROP VIEW gv_users;

CREATE OR REPLACE VIEW gv_users AS 
 SELECT p.id AS person_id, COALESCE(p.first_name::text || ' '::text, ''::text) || p.surname::text AS name, u.id, u.username, cr.title AS core_role, p.deleted
   FROM people p
   LEFT JOIN users u ON p.id = u.person_id and u.deleted='f'
   LEFT JOIN core_roles cr ON u.core_role_id = cr.id
  WHERE p.email_address IS NOT NULL;