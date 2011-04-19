CREATE OR REPLACE VIEW list_people AS 
 SELECT p.id, p.first_name, p.surname, p.initials, (p.first_name::text || ' '::text) || p.surname::text AS caption, uw.website_id
   FROM people p
   JOIN users us ON us.person_id = p.id
   LEFT JOIN users_websites uw ON uw.user_id = us.id AND uw.site_role_id IS NOT NULL
UNION ALL 
 SELECT p.id, p.first_name, p.surname, p.initials, (p.first_name::text || ' '::text) || p.surname::text AS caption, NULL::integer AS website_id
   FROM people p
   LEFT JOIN users us ON us.person_id = p.id
   WHERE us.id IS NULL;