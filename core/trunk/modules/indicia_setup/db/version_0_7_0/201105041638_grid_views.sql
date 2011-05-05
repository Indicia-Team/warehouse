DROP VIEW gv_people;

CREATE OR REPLACE VIEW gv_people AS 
         SELECT p.id, p.first_name, p.surname, p.initials, (p.first_name::text || ' '::text) || p.surname::text AS caption, 
             p.email_address, uw.website_id
           FROM people p
   LEFT JOIN users us ON us.person_id = p.id AND us.deleted=false
   LEFT JOIN users_websites uw ON uw.user_id = us.id AND uw.site_role_id IS NOT NULL
   WHERE p.deleted=false;
   
CREATE OR REPLACE VIEW gv_taxon_lists AS 
 SELECT t.id, t.title, t.website_id, t.parent_id, t.description
   FROM taxon_lists t
  WHERE t.deleted = false;