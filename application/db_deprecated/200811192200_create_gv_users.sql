ALTER TABLE users
ADD COLUMN username character varying(30),
ADD COLUMN password character varying;

UPDATE users
SET username=surname
FROM people
WHERE people.id=users.person_id;

ALTER TABLE users
ALTER COLUMN username SET NOT NULL;

CREATE OR REPLACE VIEW gv_users AS
 SELECT u.id, u.username, cr.title as core_role
   FROM users u
   LEFT JOIN core_roles cr on u.core_role_id = cr.id;
