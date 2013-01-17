ALTER TABLE people
ADD COLUMN external_key character varying(50);

COMMENT ON COLUMN people.external_key IS 'For people imported from an external system, provides a field to store the external system''s primary key for the record allowing re-synchronisation.';

DROP VIEW detail_people;

CREATE OR REPLACE VIEW detail_people AS 
 SELECT p.id, p.first_name, p.surname, p.initials, p.email_address, p.website_url, p.external_key,
     p.created_by_id, c.username AS created_by, p.updated_by_id, u.username AS updated_by, uw.website_id
   FROM people p
   JOIN users c ON c.id = p.created_by_id
   JOIN users u ON u.id = p.updated_by_id
   LEFT JOIN users us ON us.person_id = p.id
   LEFT JOIN users_websites uw ON uw.user_id = us.id AND uw.site_role_id IS NOT NULL
  WHERE p.deleted = false;