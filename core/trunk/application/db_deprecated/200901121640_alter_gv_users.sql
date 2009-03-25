DROP VIEW gv_users;
CREATE OR REPLACE VIEW gv_users AS
 SELECT p.id as person_id, COALESCE (p.first_name || ' ', '') || p.surname as name, u.id, u.username, cr.title as core_role
   FROM people p
   LEFT JOIN users u on p.id = u.person_id
   LEFT JOIN core_roles cr on u.core_role_id = cr.id
   WHERE p.email_address is not null;