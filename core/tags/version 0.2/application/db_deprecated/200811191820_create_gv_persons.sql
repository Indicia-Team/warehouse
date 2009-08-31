CREATE OR REPLACE VIEW gv_persons AS
 SELECT p.id, p.first_name, p.surname, p.initials, p.email_address, p.website_url, 
        CASE (u.core_role_id IS NOT NULL) WHEN true THEN 'Yes' ELSE '' END as is_core_user,
        CASE (u.id IS NOT NULL) WHEN true THEN 'Yes' ELSE '' END as is_user
   FROM people p
   LEFT JOIN users u on p.id = u.person_id;