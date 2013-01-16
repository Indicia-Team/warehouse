UPDATE site_roles SET title='Editor' WHERE id=2;
UPDATE site_roles SET title='User' WHERE id=3;

UPDATE users_websites 
SET site_role_id=CASE site_role_id WHEN 2 THEN 3 WHEN 3 THEN 2 END
WHERE site_role_id IN (2,3);