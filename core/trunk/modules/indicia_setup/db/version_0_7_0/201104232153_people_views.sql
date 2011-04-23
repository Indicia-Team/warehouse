-- View: detail_people

CREATE OR REPLACE VIEW detail_people AS 
   SELECT p.id, p.first_name, p.surname, p.initials, p.email_address, p.website_url, p.created_by_id, c.username AS created_by, p.updated_by_id, u.username AS updated_by, uw.website_id
   FROM people p
   JOIN users c ON c.id = p.created_by_id
   JOIN users u ON u.id = p.updated_by_id
   LEFT JOIN users us ON us.person_id = p.id AND us.deleted=false
   LEFT JOIN users_websites uw ON uw.user_id = us.id AND uw.site_role_id IS NOT NULL
   WHERE p.deleted=false;

-- View: list_people

CREATE OR REPLACE VIEW list_people AS 
   SELECT p.id, p.first_name, p.surname, p.initials, (p.first_name::text || ' '::text) || p.surname::text AS caption, uw.website_id
   FROM people p
   LEFT JOIN users us ON us.person_id = p.id AND us.deleted=false
   LEFT JOIN users_websites uw ON uw.user_id = us.id AND uw.site_role_id IS NOT NULL
   WHERE p.deleted=false;





