CREATE OR REPLACE VIEW list_users AS 
 SELECT u.id, u.username, uw.website_id, u.person_id
   FROM users u
   JOIN users_websites uw ON u.id = uw.user_id AND uw.site_role_id IS NOT NULL
  WHERE u.deleted = false;