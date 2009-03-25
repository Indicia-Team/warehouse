COMMENT ON COLUMN users_websites.site_role_id IS 'Foreign key to the site_roles table. Identifies the role of the user on this specific site.';

ALTER TABLE users_websites ADD COLUMN registration_datetime timestamp without time zone;
ALTER TABLE users_websites ALTER COLUMN registration_datetime SET STORAGE PLAIN;
COMMENT ON COLUMN users_websites.registration_datetime IS 'Date and time of registration on this website.';

ALTER TABLE users_websites ADD COLUMN last_login_datetime timestamp without time zone;
ALTER TABLE users_websites ALTER COLUMN last_login_datetime SET STORAGE PLAIN;
COMMENT ON COLUMN users_websites.last_login_datetime IS 'Date and time of last login to this website.';
