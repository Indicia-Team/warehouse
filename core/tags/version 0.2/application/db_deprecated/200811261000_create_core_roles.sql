INSERT INTO core_roles (title, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('CoreAdmin', now(), 1, now(), 1);
INSERT INTO core_roles (title, created_on, created_by_id, updated_on, updated_by_id)
VALUES ('SiteController', now(), 1, now(), 1);

UPDATE users
SET core_role_id=core_roles.id
FROM core_roles
WHERE core_roles.title='SiteController';

UPDATE users
SET core_role_id=core_roles.id
FROM core_roles
WHERE users.id=(select min(id) from users) AND core_roles.title='CoreAdmin';
