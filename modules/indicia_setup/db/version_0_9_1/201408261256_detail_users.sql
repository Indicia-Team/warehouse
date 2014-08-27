CREATE OR REPLACE VIEW detail_users AS 
 SELECT u.id, u.username, uw.website_id, u.person_id, u.created_by_id, cu.username AS created_by, u.updated_by_id, u.created_on, u.updated_on, uu.username AS updated_by, 
     p.surname, p.first_name, p.surname::text || COALESCE(', '::text || p.first_name::text, ''::text) AS person_name, p.email_address,
     p.surname::text || COALESCE(', '::text || p.first_name::text, ''::text) || ' (' || p.email_address || ')' AS name_and_email
   FROM users u
   JOIN users cu ON cu.id = u.created_by_id
   JOIN users uu ON uu.id = u.updated_by_id
   JOIN users_websites uw ON u.id = uw.user_id AND uw.site_role_id IS NOT NULL
   JOIN people p ON p.id = u.person_id AND p.deleted = false
  WHERE u.deleted = false;