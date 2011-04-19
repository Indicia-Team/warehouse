CREATE OR REPLACE VIEW report_users AS
SELECT u.id, u.person_id 
FROM users u
WHERE u.deleted = false;

CREATE OR REPLACE VIEW report_websites AS
SELECT w.id, w.title, w.description, w.url 
FROM websites w
WHERE w.deleted = false;

CREATE OR REPLACE VIEW report_people AS
SELECT p.id, p.first_name, p.surname, p.initials, p.title_id 
FROM people p
WHERE p.deleted = false;

CREATE OR REPLACE VIEW report_users_websites AS
SELECT uw.id, uw.user_id, uw.website_id, uw.site_role_id
FROM users_websites uw
WHERE uw.deleted = false;
